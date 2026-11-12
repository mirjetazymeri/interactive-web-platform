<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Widgets\Types\ProjectsWidgetTypeService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function show(Request $request, int $widgetId, string $slug, ProjectsWidgetTypeService $projects)
    {
        $project = $projects->publicItem($request, $widgetId, $slug);

        return view('storefront.projects.show', ['project' => $project,
            'metaTitle' => $project['name'], 'metaDescription' => $project['description'] ?? '', 'isHome' => false]);
    }
}
