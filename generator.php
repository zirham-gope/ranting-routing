<?php
echo "Memulai proses fetch ASN...\n";

// Kita pisah menjadi 2 kelompok target
$targets = [
    'yt_meta' => [
        'list_name' => 'IP_YT_Meta',
        'asn' => [
            '15169', '36040', // ASN Google / YouTube
            '32934'           // ASN Meta (FB, IG, WA)
        ]
    ],
    'tiktok' => [
        'list_name' => 'IP_TikTok',
        'asn' => [
            '138699', '396986', '139032', '396987' // ASN TikTok
        ]
    ]
];

foreach ($targets as $file => $data) {
    $output = "/ip firewall address-list\n";
    $output .= "/ip firewall address-list remove [find list=" . $data['list_name'] . "] skip-empty=yes\n";
    
    foreach ($data['asn'] as $asn) {
        $url = "https://api.bgpview.io/asn/{$asn}/prefixes";
        $response = @file_get_contents($url);
        
        if ($response) {
            $json = json_decode($response, true);
            if (isset($json['data']['ipv4_prefixes'])) {
                foreach ($json['data']['ipv4_prefixes'] as $prefix) {
                    $ip = $prefix['prefix'];
                    $output .= "add list={$data['list_name']} address={$ip}\n";
                }
            }
        }
    }
    
    // Simpan menjadi file yt_meta.rsc dan tiktok.rsc
    file_put_contents($file . '.rsc', $output);
    echo "Selesai generate {$file}.rsc\n";
}
?>
