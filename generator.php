<?php
echo "Memulai proses fetch ASN menggunakan API RIPE...\n";

// Membagi menjadi 2 kelompok file untuk MikroTik
$targets = [
    'yt_meta' => [
        'list_name' => 'IP_YT_Meta',
        'asn' => ['15169', '36040', '32934'] // Google, YouTube, Meta
    ],
    'tiktok' => [
        'list_name' => 'IP_TikTok',
        'asn' => ['138699', '396986', '139032', '396987'] // TikTok
    ]
];

// Context sederhana untuk request
$options = [
    "http" => [
        "header" => "User-Agent: RantingID-Bot/1.0\r\n"
    ]
];
$context = stream_context_create($options);

foreach ($targets as $file => $data) {
    $output = "/ip firewall address-list\n";
    $output .= "/ip firewall address-list remove [find list=" . $data['list_name'] . "] skip-empty=yes\n";
    
    foreach ($data['asn'] as $asn) {
        // Menggunakan Endpoint API RIPE Stat (Sangat kebal blokir)
        $url = "https://stat.ripe.net/data/announced-prefixes/data.json?resource=AS{$asn}";
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $json = json_decode($response, true);
            if (isset($json['data']['prefixes'])) {
                $count = 0;
                foreach ($json['data']['prefixes'] as $prefix_data) {
                    $ip = $prefix_data['prefix'];
                    
                    // Filter: Pastikan hanya mengambil IPv4 (mengabaikan IPv6 jika ada)
                    if (strpos($ip, ':') === false) {
                        $output .= "add list={$data['list_name']} address={$ip}\n";
                        $count++;
                    }
                }
                echo "ASN {$asn} sukses didapat: {$count} subnet IP.\n";
            }
        } else {
            echo "GAGAL mengambil data untuk ASN {$asn}!\n";
        }
        
        // Jeda 1 detik agar aman
        sleep(1);
    }
    
    // Menyimpan menjadi file .rsc
    file_put_contents($file . '.rsc', $output);
    echo "=== Selesai generate {$file}.rsc ===\n\n";
}
?>
