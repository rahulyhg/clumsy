<?php

namespace Clumsy\CMS\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Clumsy\CMS\Support\ResourceNameResolver;
use Clumsy\Utils\Facades\HTTP;

class Bakery
{
    protected $prefix;

    protected $parents;

    protected $breadcrumb = [];

    public function __construct(
        Request $request,
        UrlGenerator $url,
        ResourceNameResolver $labeler
    )
    {
        $this->request = $request;

        $this->url = $url;

        $this->labeler = $labeler;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function breadcrumb($hierarchy, $action)
    {
        $this->action = $action;

        extract($hierarchy);

        $resourceName = $current->resourceName();

        // Home
        $this->breadcrumb[trans('clumsy::breadcrumb.home')] = $this->url->to($this->prefix);

        switch ($action) {

            case 'create':
                // Fall through
            case 'edit':

                if (count($parents)) {

                    $parentCrumbs = [];

                    foreach (array_reverse($parents) as $parent) {

                        $parentResourceName = $parent->resourceName();
                        $parentRoutePrefix = $this->prefix ? "{$this->prefix}.{$parentResourceName}" : $parentResourceName;

                        $parentCrumbs[$this->labeler->displayNamePlural($current)] = HTTP::queryStringAdd($this->url->route("{$parentRoutePrefix}.edit", $parent->id), 'show', $resourceName);
                        $parentCrumbs[trans('clumsy::titles.edit_item', ['resource' => $this->labeler->displayName($parent)])] = $this->url->route("{$parentRoutePrefix}.edit", $parent->id);
                        $parentCrumbs[$this->labeler->displayNamePlural($parent)] = $this->url->route("{$parentRoutePrefix}.index");

                        $current = $parent;
                    }

                    $this->breadcrumb = $this->breadcrumb + array_reverse($parentCrumbs);
                } else {
                    $routePrefix = $this->prefix ? "{$this->prefix}.{$resourceName}" : $resourceName;
                    $this->breadcrumb[$this->labeler->displayNamePlural($current)] = $this->url->route("{$routePrefix}.index");
                }

                $this->breadcrumb[trans("clumsy::breadcrumb.{$this->action}")] = '';

                break;

            case 'reorder':

                $routePrefix = $this->prefix ? "{$this->prefix}.{$resourceName}" : $resourceName;
                $this->breadcrumb[$this->labeler->displayNamePlural($current)] = $this->url->route("{$routePrefix}.index");
                $this->breadcrumb[trans('clumsy::titles.reorder', ['resources' => $this->labeler->displayNamePlural($current)])] = '';
                break;

            case 'index-of-type':
                // Fall through
            case 'index':
                $this->breadcrumb[$this->labeler->displayNamePlural($current)] = '';
                break;

            default:
                $this->breadcrumb[trans("clumsy::breadcrumb.$action")] = '';
        }

        return $this->breadcrumb;
    }
}
