<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminMenu;
use Illuminate\Http\Request;

class AdminMenuController extends Controller
{
    protected $adminMenu;

    public function __construct(AdminMenu $adminMenu)
    {
        $this->adminMenu = $adminMenu;
    }

    /**
     * Получить меню для текущего пользователя
     */
    public function index(Request $request)
    {
        $menu = $this->adminMenu->getMenuJson($request->user());

        return response()->json([
            'menu' => $menu,
        ]);
    }
}
