<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;

class TestController extends Controller
{
    public function index()
    {
        $categories = Category::with('children')->get()->take(1);
        return response()->json($categories);
    }
}
