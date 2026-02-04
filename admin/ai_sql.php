<?php
function ask_ai_for_sql($prompt)
{
    $endpoint = "azure";
    $apiKey   = "azure";

    $systemPrompt = "
Sen deneyimli bir SQL uzmanısın.
Sadece MYSQL sorgusu üret.
ASLA açıklama yazma.
ASLA ``` koyma.
Sadece SQL yaz.

Kullanabileceğin prosedürler: GetOrderInvoice, GetFullReviewDetails, GetUserCartDetailed, GetSellerFinancialReport, GetUserFavoritesWithBonus, GetTrendingProducts, GetPaymentLocationReport, GetLiveDeliveryTracking, GetUserPointEarnings, GetRestaurantStats, GetCartTotalCalculation, GetPendingFinancials, GetRestaurantRatingDeep, GetSystemActionLogs, GetSellerMenuDetailed.
Kullanabileceğin triggerler: AfterOrderDelivered_Points, UpdateRestaurantRating, LogNewFavorite.
Veritabanı tablosu:
users(id, fullname, email, role, created_at)
roller: admin, seller, customer
";

    $data = [
        "messages" => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "api-key: $apiKey"
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    return $json['choices'][0]['message']['content'] ?? '';
}
