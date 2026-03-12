<?php namespace Extends\Backend\Widgets;

use Db;
use Str;
use Html;
use Lang;
use Backend;
use DbDongle;
use Carbon\Carbon;
use Winter\Storm\Html\Helper as HtmlHelper;
use Winter\Storm\Router\Helper as RouterHelper;
use System\Helpers\DateTime as DateTimeHelper;
use System\Classes\PluginManager;
use System\Classes\MediaLibrary;
use System\Classes\ImageResizer;
use Backend\Classes\ListColumn;
use Backend\Classes\WidgetBase;
use Winter\Storm\Database\Model;
use ApplicationException;
use BackendAuth;
use Backend\Widgets\Lists as WidgetsList;
use Flash;
use Session;
use Input;

/**
 * Eprog List Widget
 */
class Lists extends WidgetsList
{

    public function prepareVars()
    {

        $this->recordsPerPage = config("app.perPage.default");
        $this->vars['cssClasses'] = implode(' ', $this->cssClasses);
        $this->vars['columns'] = $this->getVisibleColumns();
        $this->vars['columnTotal'] = $this->getTotalColumns();
        $this->vars['records'] = $this->getRecords();
        $this->vars['noRecordsMessage'] = trans($this->noRecordsMessage);
        $this->vars['showCheckboxes'] = $this->showCheckboxes;
        $this->vars['showSetup'] = $this->showSetup;
        $this->vars['showPagination'] = $this->showPagination;
        $this->vars['showPageNumbers'] = $this->showPageNumbers;
        $this->vars['showSorting'] = $this->showSorting;
        $this->vars['sortColumn'] = $this->getSortColumn();
        $this->vars['sortDirection'] = $this->sortDirection;
        $this->vars['showTree'] = $this->showTree;
        $this->vars['treeLevel'] = 0;

        if($this->controller->name == "drive")
            $this->vars['showPagination'] = 0;

        if ($this->showPagination) {

            $this->model->setRecordsPerPage($this->recordsPerPage);

            $this->vars['pageCurrent'] = $this->model->getCurrent();//$this->records->currentPage();
            // Store the currently visited page number in the session so the same
            // data can be displayed when the user returns to this list.
            $this->putSession('lastVisitedPage', $this->vars['pageCurrent']);
            if ($this->showPageNumbers) {
                $this->vars['recordTotal'] = $this->model->getTotal();//$this->records->total();
                $this->vars['pageLast'] = $this->model->getLast();//$this->records->lastPage();
                $this->vars['pageFrom'] = $this->model->getPageFrom();//$this->records->firstItem() ?? 0;
                $this->vars['pageTo'] = $this->model->getPageTo();//$this->records->lastItem() ?? 0;
            }
            else {
                $this->vars['hasMorePages'] = $this->records->hasMorePages();
            }
        }
        else {
            $this->vars['recordTotal'] = $this->records->count();
            $this->vars['pageCurrent'] = 1;
        }


        
    }



    /**
     * Returns all the records from the supplied model, after filtering.
     * @return Collection
     */
    protected function getRecords()
    {
        $query = $this->prepareQuery();

        if ($this->showTree) {
            $records = $query->getNested();
        }
        elseif ($this->showPagination) {
            $method            = $this->showPageNumbers ? 'paginate' : 'simplePaginate';
            $currentPageNumber = $this->getCurrentPageNumber($query);
            $records = $query->{$method}($this->recordsPerPage, $currentPageNumber);

        }
        else {
            $records = $query->get();
        }

        /**
         * @event backend.list.extendRecords
         * Provides an opportunity to modify and / or return the `$records` Collection object before the widget uses it.
         *
         * Example usage:
         *
         *     Event::listen('backend.list.extendRecords', function ($listWidget, $records) {
         *         $model = MyModel::where('always_include', true)->first();
         *         $records->prepend($model);
         *     });
         *
         * Or
         *
         *     $listWidget->bindEvent('list.extendRecords', function ($records) {
         *         $model = MyModel::where('always_include', true)->first();
         *         $records->prepend($model);
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('backend.list.extendRecords', [&$records])) {
            $records = $event;
        }

        return $this->records = $records;
    }


    /**
     * Applies the search constraint to a query.
     */
    protected function applySearchToQuery($query, $columns, $boolean = 'and')
    {
        $term = $this->searchTerm;

        if ($scopeMethod = $this->searchScope) {
            $searchMethod = $boolean == 'and' ? 'where' : 'orWhere';
            $query->$searchMethod(function ($q) use ($term, $columns, $scopeMethod) {
                $q->$scopeMethod($term, $columns);
            });
        }
        else {
            $searchMethod = $boolean == 'and' ? 'searchWhere' : 'orSearchWhere';
           // $query->$searchMethod($term, $columns, $this->searchMode);
        }

        Session::put($this->controller->name."_term", $term);

    }
   
}
