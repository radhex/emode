<?php namespace Extends\Backend\Behaviors;

use Lang;
use Event;
use Flash;
use ApplicationException;
use Backend\Classes\ControllerBehavior;
use Backend\Behaviors\ListController as BehaviorsListController;
use Input;

/**
 * Adds features for working with backend lists.
 *
 */
class ListController extends BehaviorsListController
{
  

    /**
     * Prepare the widgets used by this action
     * @return \Backend\Widgets\Lists
     */
    public function makeList($definition = null)
    {
        if (!$definition || !isset($this->listDefinitions[$definition])) {
            $definition = $this->primaryDefinition;
        }

        $listConfig = $this->controller->listGetConfig($definition);

        /*
         * Create the model
         */
        $class = $listConfig->modelClass;
        $model = new $class;
        $model = $this->controller->listExtendModel($model, $definition);

        /*
         * Prepare the list widget
         */
        $columnConfig = $this->makeConfig($listConfig->list);
        $columnConfig->model = $model;
        $columnConfig->alias = $definition;

        /*
         * Prepare the columns configuration
         */
        $configFieldsToTransfer = [
            'recordUrl',
            'recordOnClick',
            'recordsPerPage',
            'perPageOptions',
            'showPageNumbers',
            'noRecordsMessage',
            'defaultSort',
            'showSorting',
            'showSetup',
            'showCheckboxes',
            'showTree',
            'treeExpanded',
            'customViewPath',
        ];

        foreach ($configFieldsToTransfer as $field) {
            if (isset($listConfig->{$field})) {
                $columnConfig->{$field} = $listConfig->{$field};
            }
        }

        /*
         * List Widget with extensibility
         */
        $widget = $this->makeWidget(\Extends\Backend\Widgets\Lists::class, $columnConfig);
   

        $widget->bindEvent('list.extendColumns', function () use ($widget) {
            $this->controller->listExtendColumns($widget);
        });

        $widget->bindEvent('list.extendQueryBefore', function ($query) use ($definition) {
            $this->controller->listExtendQueryBefore($query, $definition);
        });

        $widget->bindEvent('list.extendQuery', function ($query) use ($definition) {
            $this->controller->listExtendQuery($query, $definition);
        });

        $widget->bindEvent('list.extendRecords', function ($records) use ($definition) {
            return $this->controller->listExtendRecords($records, $definition);
        });

        $widget->bindEvent('list.injectRowClass', function ($record) use ($definition) {
            return $this->controller->listInjectRowClass($record, $definition);
        });

        $widget->bindEvent('list.overrideColumnValue', function ($record, $column, $value) use ($definition) {
            return $this->controller->listOverrideColumnValue($record, $column->columnName, $definition);
        });

        $widget->bindEvent('list.overrideHeaderValue', function ($column, $value) use ($definition) {
            return $this->controller->listOverrideHeaderValue($column->columnName, $definition);
        });
      
        $widget->bindToController();

        /*
         * Prepare the toolbar widget (optional)
         */
        if (isset($listConfig->toolbar)) {
            $toolbarConfig = $this->makeConfig($listConfig->toolbar);
            $toolbarConfig->alias = $widget->alias . 'Toolbar';
            $toolbarWidget = $this->makeWidget(\Extends\Backend\Widgets\Toolbar::class, $toolbarConfig);
            $toolbarWidget->bindToController();
            $toolbarWidget->cssClasses[] = 'list-header';

            /*
             * Link the Search Widget to the List Widget
             */
            if ($searchWidget = $toolbarWidget->getSearchWidget()) {
                $searchWidget->bindEvent('search.submit', function () use ($widget, $searchWidget) {
                    $widget->setSearchTerm($searchWidget->getActiveTerm(), true);
                    return $widget->onRefresh();
                });

                $widget->setSearchOptions([
                    'mode' => $searchWidget->mode,
                    'scope' => $searchWidget->scope,
                ]);


                if(Input::has("term"))
                    $searchWidget->setActiveTerm(Input::get("term"));
                else
                    $searchWidget->setActiveTerm("");

                // Find predefined search term
                $widget->setSearchTerm($searchWidget->getActiveTerm());
            }

            $this->toolbarWidgets[$definition] = $toolbarWidget;

        }

        /*
         * Prepare the filter widget (optional)
         */
        if (isset($listConfig->filter)) {
            $filterConfig = $this->makeConfig($listConfig->filter);

            if (!empty($filterConfig->scopes)) {
                $widget->cssClasses[] = 'list-flush';

                $filterConfig->alias = $widget->alias . 'Filter';
                $filterWidget = $this->makeWidget(\Backend\Widgets\Filter::class, $filterConfig);
                $filterWidget->bindToController();

                /*
                * Filter the list when the scopes are changed
                */
                $filterWidget->bindEvent('filter.update', function () use ($widget, $filterWidget) {
                    return $widget->onFilter();
                });

                /*
                * Filter Widget with extensibility
                */
                $filterWidget->bindEvent('filter.extendScopes', function () use ($filterWidget) {
                    $this->controller->listFilterExtendScopes($filterWidget);
                });

                /*
                * Extend the query of the list of options
                */
                $filterWidget->bindEvent('filter.extendQuery', function ($query, $scope) {
                    $this->controller->listFilterExtendQuery($query, $scope);
                });

                // Apply predefined filter values
                $widget->addFilter([$filterWidget, 'applyAllScopesToQuery']);

                $this->filterWidgets[$definition] = $filterWidget;
            }
        }

        return $widget;
    }

   
}
