<?php
echo "Memulai proses fetch ASN...\n";

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

// KONFIGURASI HEADER (Sangat Penting agar tidak diblokir BGPView)
$options = [
    "http" => [
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n"
    ]
];
$context = stream_context_create($options);

foreach ($targets as $file => $data) {
    $output = "/ip firewall address-list\n";
    $output .= "/ip firewall address-list remove [find list=" . $data['list_name'] . "] skip-empty=yes\n";
    
    foreach ($data['asn'] as $asn) {
        $url = "https://api.bgpview.io/asn/{$asn}/prefixes";
        
        // Gunakan context agar API mengira ini browser asli
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $json = json_decode($response, true);
            if (isset($json['data']['ipv4_prefixes'])) {
                $count = 0;
                foreach ($json['data']['ipv4_prefixes'] as $prefix) {
                    $ip = $prefix['prefix'];
                    $output .= "add list={$data['list_name']} address={$ip}\n";
                    $count++;
                }
                echo "ASN {$asn} sukses didapat: {$count} subnet IP.\n";
            }
        } else {
            echo "GAGAL mengambil data untuk ASN {$asn}!\n";
        }
        
        // Jeda 1 detik agar tidak diblokir karena terlalu cepat (Rate Limit)
        sleep(1);
    }
    
    // Menyimpan menjadi file .rsc
    file_put_contents($file . '.rsc', $output);
    echo "=== Selesai generate {$file}.rsc ===\n\n";
}
?>
