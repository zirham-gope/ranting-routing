<?php
echo "Memulai proses fetch ASN menggunakan API RIPE...\n";

$targets = [
    'yt_meta' => [
        'list_name' => 'IP_YT_Meta',
        'asn' => ['15169', '36040', '32934'] 
    ],
    'tiktok' => [
        'list_name' => 'IP_TikTok',
        'asn' => ['138699', '396986', '139032', '396987'] 
    ]
];

$options = [
    "http" => [
        "header" => "User-Agent: RantingID-Bot/1.0\r\n"
    ]
];
$context = stream_context_create($options);

foreach ($targets as $file => $data) {
    $output = "/ip firewall address-list\n";
    
    // BARIS REMOVE SUDAH DIHAPUS DARI SINI AGAR IMPORT TIDAK ERROR
    
    foreach ($data['asn'] as $asn) {
        $url = "https://stat.ripe.net/data/announced-prefixes/data.json?resource=AS{$asn}";
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $json = json_decode($response, true);
            if (isset($json['data']['prefixes'])) {
                foreach ($json['data']['prefixes'] as $prefix_data) {
                    $ip = $prefix_data['prefix'];
                    // Hanya ambil IPv4
                    if (strpos($ip, ':') === false) {
                        $output .= "add list={$data['list_name']} address={$ip}\n";
                    }
                }
            }
        }
        sleep(1);
    }
    file_put_contents($file . '.rsc', $output);
    echo "=== Selesai generate {$file}.rsc ===\n\n";
}
?>
