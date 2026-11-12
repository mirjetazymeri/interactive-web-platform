<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Cms\MenuBuilderService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct(private readonly MenuBuilderService $menus) {}

    public function index(Request $req)
    {
        $loc = $req->query('location');
        if ($loc) {
            return $this->show($req, $loc);
        }

        return response()->json([
            'data' => [
                'header' => $this->menus->build('header'),
                'footer' => $this->menus->build('footer'),
            ],
        ]);
    }

    public function show(Request $req, string $location)
    {
        abort_unless(in_array($location, ['header', 'footer'], true), 404);

        return response()->json(['data' => $this->menus->build($location)]);
    }
}
