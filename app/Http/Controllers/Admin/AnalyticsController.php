<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            "revenue" => 2750000,
            "orders" => 143,
            "customers" => 89,
            "conversion_rate" => 4.6,

            "revenue_chart" => [
                ["month"=>"Jan","revenue"=>120000],
                ["month"=>"Feb","revenue"=>180000],
                ["month"=>"Mar","revenue"=>250000],
                ["month"=>"Apr","revenue"=>400000],
                ["month"=>"May","revenue"=>520000],
                ["month"=>"Jun","revenue"=>760000],
            ],

            "orders_chart" => [
                ["month"=>"Jan","orders"=>12],
                ["month"=>"Feb","orders"=>18],
                ["month"=>"Mar","orders"=>25],
                ["month"=>"Apr","orders"=>34],
                ["month"=>"May","orders"=>42],
                ["month"=>"Jun","orders"=>51],
            ],

            "categories" => [
                ["name"=>"Phones","sales"=>45],
                ["name"=>"Laptops","sales"=>25],
                ["name"=>"Accessories","sales"=>20],
                ["name"=>"Tablets","sales"=>10],
            ],

            "countries" => [
                [
                    "country"=>"Nigeria",
                    "orders"=>90,
                    "sales"=>1800000,
                    "percent"=>65
                ],
                [
                    "country"=>"Ghana",
                    "orders"=>28,
                    "sales"=>650000,
                    "percent"=>23
                ],
                [
                    "country"=>"Kenya",
                    "orders"=>12,
                    "sales"=>300000,
                    "percent"=>12
                ],
            ]
        ]);
    }
}