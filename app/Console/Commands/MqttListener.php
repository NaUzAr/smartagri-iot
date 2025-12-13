<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MqttListener extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mqtt:listen 
                            {--host=localhost : MQTT Broker Host}
                            {--port=1883 : MQTT Broker Port}
                            {--username= : MQTT Username (optional)}
                            {--password= : MQTT Password (optional)}';

    /**
     * The console command description.
     */
    protected $description = 'Listen to MQTT broker for sensor data and store to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->option('host');
        $port = (int) $this->option('port');
        $username = $this->option('username');
        $password = $this->option('password');

        $this->info("🚀 Starting MQTT Listener...");
        $this->info("   Broker: {$host}:{$port}");

        try {
            // Setup connection
            $connectionSettings = new ConnectionSettings();

            if ($username && $password) {
                $connectionSettings = $connectionSettings
                    ->setUsername($username)
                    ->setPassword($password);
            }

            $connectionSettings = $connectionSettings
                ->setKeepAliveInterval(60)
                ->setConnectTimeout(10);

            // Create MQTT client
            $mqtt = new MqttClient($host, $port, 'laravel-listener-' . uniqid());
            $mqtt->connect($connectionSettings, true);

            $this->info("✅ Connected to MQTT Broker!");

            // Get all devices and subscribe to their topics
            $devices = Device::all();

            if ($devices->isEmpty()) {
                $this->warn("⚠️  No devices found. Create devices first via admin panel.");
                $this->info("   Listening for wildcard topic: sensor/#");

                $mqtt->subscribe('sensor/#', function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);
            } else {
                foreach ($devices as $device) {
                    $topic = $device->mqtt_topic;
                    $this->info("📡 Subscribed to: {$topic}");

                    $mqtt->subscribe($topic, function ($topic, $message) {
                        $this->processMessage($topic, $message);
                    }, 0);
                }

                // Also subscribe to wildcard for new devices
                $mqtt->subscribe('sensor/#', function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);
            }

            $this->info("");
            $this->info("👂 Listening for messages... (Press Ctrl+C to stop)");
            $this->info("─────────────────────────────────────────────────");

            // Loop forever
            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error('MQTT Listener Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Process incoming MQTT message
     */
    private function processMessage($topic, $message)
    {
        $timestamp = now()->format('H:i:s');
        $this->line("[{$timestamp}] 📨 Topic: {$topic}");
        $this->line("           Data: {$message}");

        try {
            // Parse JSON message
            $data = json_decode($message, true);

            if (!$data) {
                $this->warn("           ⚠️  Invalid JSON format");
                return;
            }

            // Cari device berdasarkan topic ATAU token
            $device = null;

            // Coba cari by MQTT topic
            $device = Device::where('mqtt_topic', $topic)->first();

            // Kalau tidak ketemu, coba cari by token di data
            if (!$device && isset($data['token'])) {
                $device = Device::where('token', $data['token'])->first();
            }

            if (!$device) {
                $this->warn("           ⚠️  Device not found for topic: {$topic}");
                return;
            }

            // Prepare data untuk insert
            $tableName = $device->table_name;

            if (!\Schema::hasTable($tableName)) {
                $this->warn("           ⚠️  Table {$tableName} does not exist");
                return;
            }

            // Get sensor columns from device
            $sensors = $device->sensors;
            $insertData = ['recorded_at' => now()];

            foreach ($sensors as $sensor) {
                $sensorName = $sensor->sensor_name;
                if (isset($data[$sensorName])) {
                    $insertData[$sensorName] = (float) $data[$sensorName];
                }
            }

            // Insert to database
            DB::table($tableName)->insert($insertData);

            $this->info("           ✅ Data saved to {$tableName}");

        } catch (\Exception $e) {
            $this->error("           ❌ Error: " . $e->getMessage());
            Log::error('MQTT Process Error: ' . $e->getMessage());
        }
    }
}
