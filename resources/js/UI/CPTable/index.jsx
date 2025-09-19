import React, { useState, useEffect, useMemo, useCallback } from 'react';
import {
    Search,
    Filter,
    ChevronUp,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    X,
    Check,
    FileQuestion,
} from 'lucide-react';
import classNames from 'classnames';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '../ToolTip';

const selectableActions = {
    selectRow: 'SELECT_ID',
    selectPage: 'SELECT_ALL_FROM_PAGE',
    selectAll: 'SELECT_ALL_FROM_TABLE',
};

const TableSkeleton = ({ columns, rows = 5, selectable = false }) => {
    return (
        <>
            {Array.from({ length: rows }).map((_, rowIndex) => (
                <tr key={rowIndex} className="animate-pulse">
                    {selectable && (
                        <td className="px-6 py-4">
                            <div className="h-4 w-4 bg-gray-200 rounded"></div>
                        </td>
                    )}
                    {columns.map((_, colIndex) => (
                        <td key={colIndex} className="px-6 py-4">
                            <div className="h-4 bg-gray-200 rounded w-full"></div>
                        </td>
                    ))}
                </tr>
            ))}
        </>
    );
};

const TableLoading = ({
    columns,
    message = 'Loading...',
    showSkeleton = true,
    skeletonRows = 10,
    selectable = false,
}) => {
    if (showSkeleton) {
        return (
            <TableSkeleton
                columns={columns}
                rows={skeletonRows}
                selectable={selectable}
            />
        );
    }

    return (
        <tr>
            <td
                colSpan={columns.length + (selectable ? 1 : 0)}
                className="px-6 py-12 text-center text-gray-500"
            >
                <div className="flex items-center justify-center gap-2">
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-500"></div>
                    {message}
                </div>
            </td>
        </tr>
    );
};

const TableEmptyState = ({ columns, message = 'No data available' }) => (
    <tr>
        <td
            colSpan={columns.length}
            className="px-6 py-12 text-center text-gray-500"
        >
            {message}
        </td>
    </tr>
);

const ActionButtons = ({ actionButtons }) => {
    return actionButtons?.map((button) => {
        return (
            <button
                disabled={button.disabled}
                key={button.id}
                onClick={button.onClick}
                className={classNames(
                    'flex items-center cursor-pointer whitespace-nowrap gap-2 px-4 py-2 border border-gray-300 rounded-lg  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-opacity-50 disabled:cursor-not-allowed ',
                    button.classNames
                )}
            >
                {button.innerText}
            </button>
        );
    });
};

// Search Component
const TableSearch = ({ searchTerm, onSearch, placeholder = 'Search...' }) => (
    <div className="relative flex-1 max-w-xl">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
        <input
            type="text"
            placeholder={placeholder}
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            value={searchTerm}
            onChange={(e) => onSearch(e.target.value)}
        />
    </div>
);

// Filter Input Component
const FilterInput = ({ filter, value, onChange }) => {
    const { key, type, options, placeholder } = filter;

    const baseInputClasses =
        'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent';

    switch (type) {
        case 'select':
            return (
                <select
                    className={baseInputClasses}
                    value={value || ''}
                    onChange={(e) => onChange(key, e.target.value)}
                >
                    <option value="">{placeholder || 'All'}</option>
                    {options?.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            );
        case 'date':
            return (
                <input
                    type="date"
                    className={baseInputClasses}
                    value={value || ''}
                    onChange={(e) => onChange(key, e.target.value)}
                    placeholder={placeholder}
                />
            );
        case 'daterange':
            return (
                <div className="flex gap-2">
                    <input
                        type="date"
                        className={baseInputClasses}
                        value={value?.from || ''}
                        onChange={(e) =>
                            onChange(key, { ...value, from: e.target.value })
                        }
                        placeholder="From"
                    />
                    <input
                        type="date"
                        className={baseInputClasses}
                        value={value?.to || ''}
                        onChange={(e) =>
                            onChange(key, { ...value, to: e.target.value })
                        }
                        placeholder="To"
                    />
                </div>
            );
        case 'number':
            return (
                <input
                    type="number"
                    className={baseInputClasses}
                    value={value || ''}
                    onChange={(e) => onChange(key, e.target.value)}
                    placeholder={placeholder || `Filter by ${key}`}
                />
            );
        default:
            return (
                <input
                    type="text"
                    className={baseInputClasses}
                    value={value || ''}
                    onChange={(e) => onChange(key, e.target.value)}
                    placeholder={placeholder || `Filter by ${key}`}
                />
            );
    }
};

// Filter Group Component
const FilterGroup = ({ group, activeFilters, onFilterChange }) => {
    const [isExpanded, setIsExpanded] = useState(false);

    return (
        <div className="border border-gray-200 rounded-lg">
            <button
                onClick={() => setIsExpanded(!isExpanded)}
                className="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex items-center justify-between rounded-t-lg"
            >
                <span className="font-medium text-gray-900">{group.title}</span>
                <ChevronDown
                    className={`h-4 w-4 transition-transform ${
                        isExpanded ? 'rotate-180' : ''
                    }`}
                />
            </button>

            {isExpanded && (
                <div className="p-4 space-y-4">
                    {group.filters.map((filter) => (
                        <div key={filter.key} className="space-y-2">
                            <label className="block text-sm font-medium text-gray-700">
                                {filter.label || filter.key}
                            </label>
                            <FilterInput
                                filter={filter}
                                value={activeFilters[filter.key]}
                                onChange={onFilterChange}
                            />
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

// Advanced Filter Component
const AdvancedFilters = ({
    filters,
    activeFilters,
    onFilterChange,
    onClearFilters,
}) => {
    const [showFilters, setShowFilters] = useState(false);

    const activeFilterCount = Object.keys(activeFilters).filter(
        (key) => activeFilters[key]
    ).length;

    // Check if filters are grouped
    const isGrouped = filters.some((filter) => filter.filters);

    return (
        <div className="relative">
            <button
                onClick={() => setShowFilters(!showFilters)}
                className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <Filter className="h-4 w-4" />
                <span>Filters</span>
                {activeFilterCount > 0 && (
                    <span className="bg-blue-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        {activeFilterCount}
                    </span>
                )}
            </button>

            {showFilters && (
                <div className="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                    <div className="p-4">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-medium text-gray-900">
                                Filters
                            </h3>
                            <div className="flex items-center gap-2">
                                {activeFilterCount > 0 && (
                                    <button
                                        onClick={onClearFilters}
                                        className="text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        Clear all
                                    </button>
                                )}
                                <button
                                    onClick={() => setShowFilters(false)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div className="space-y-4 max-h-96 overflow-y-auto">
                            {isGrouped
                                ? filters.map((group, index) => (
                                      <FilterGroup
                                          key={index}
                                          group={group}
                                          activeFilters={activeFilters}
                                          onFilterChange={onFilterChange}
                                      />
                                  ))
                                : filters.map((filter) => (
                                      <div
                                          key={filter.key}
                                          className="space-y-2"
                                      >
                                          <label className="block text-sm font-medium text-gray-700">
                                              {filter.label || filter.key}
                                          </label>
                                          <FilterInput
                                              filter={filter}
                                              value={activeFilters[filter.key]}
                                              onChange={onFilterChange}
                                          />
                                      </div>
                                  ))}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

// Selection Controls Component
const SelectionControls = ({ selectedAll, onSelectionChange }) => {
    return (
        <div className="flex items-center gap-4">
            <Tooltip>
                <TooltipTrigger>
                    <label
                        className={classNames(
                            'flex items-center whitespace-nowrap gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent'
                        )}
                    >
                        <input
                            type="checkbox"
                            checked={selectedAll}
                            onChange={(e) => {
                                onSelectionChange(
                                    selectableActions.selectAll,
                                    e.target.checked
                                );
                            }}
                            className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                        {selectedAll ? 'Unselect All' : 'Select All'}
                    </label>
                </TooltipTrigger>
                <TooltipContent>
                    Select all the entries from current table
                </TooltipContent>
            </Tooltip>

            {/* <div className="flex items-center gap-2">
                <input
                    type="checkbox"
                    checked={allSelected}
                    ref={(input) => {
                        if (input) input.indeterminate = someSelected;
                    }}
                    onChange={(e) => {
                        if (e.target.checked) {
                            onSelectAll();
                        } else {
                            onDeselectAll();
                        }
                    }}
                    className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span className="text-sm text-gray-700">
                    {selectedItems.length > 0
                        ? `${selectedItems.length} selected`
                        : 'Select all'}
                </span>
            </div> */}

            {/* {selectedItems.length > 0 && (
                <button
                    onClick={onDeselectAll}
                    className="text-sm text-blue-600 hover:text-blue-800"
                >
                    Clear selection
                </button>
            )} */}
        </div>
    );
};

// Table Header Component
const TableHeader = ({
    columns,
    sortConfig,
    onSort,
    sortable,
    selectable,
    selectedItems,
    tableData,
    onSelectionChange,
    selectedAll = false,
}) => {
    const allPageItemsSelected =
        tableData.length > 0 &&
        tableData.every((item) => selectedItems.includes(item.id || item));
    const somePageItemsSelected = tableData.some((item) =>
        selectedItems.includes(item.id || item)
    );

    return (
        <thead className="bg-green-100">
            <tr>
                {selectable && (
                    <th className="px-6 py-3 text-left">
                        <Tooltip>
                            <TooltipTrigger>
                                <input
                                    type="checkbox"
                                    checked={
                                        selectedAll || allPageItemsSelected
                                    }
                                    ref={(input) => {
                                        if (input) {
                                            input.indeterminate =
                                                !selectedAll &&
                                                somePageItemsSelected &&
                                                !allPageItemsSelected;
                                        }
                                    }}
                                    onChange={(e) => {
                                        onSelectionChange(
                                            selectableActions.selectPage,
                                            !allPageItemsSelected
                                        );
                                    }}
                                    className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                />
                            </TooltipTrigger>
                            <TooltipContent>
                                Select all entries from current page
                            </TooltipContent>
                        </Tooltip>
                    </th>
                )}
                {columns.map((column, index) => (
                    <th
                        key={index}
                        className={`px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ${
                            sortable && column.sortable
                                ? 'cursor-pointer hover:bg-gray-100'
                                : ''
                        } ${column.headerClassName || ''}`}
                        onClick={() =>
                            column.sortable && onSort(column.accessor)
                        }
                    >
                        <div className="flex items-center gap-2">
                            {column.header}
                            {sortable && column.sortable && (
                                <div className="flex flex-col">
                                    <ChevronUp
                                        className={`h-3 w-3 ${
                                            sortConfig.key ===
                                                column.accessor &&
                                            sortConfig.direction === 'asc'
                                                ? 'text-blue-500'
                                                : 'text-gray-400'
                                        }`}
                                    />
                                    <ChevronDown
                                        className={`h-3 w-3 -mt-1 ${
                                            sortConfig.key ===
                                                column.accessor &&
                                            sortConfig.direction === 'desc'
                                                ? 'text-blue-500'
                                                : 'text-gray-400'
                                        }`}
                                    />
                                </div>
                            )}
                        </div>
                    </th>
                ))}
            </tr>
        </thead>
    );
};

// Table Row Component
const TableRow = ({
    row,
    columns,
    rowIndex,
    striped,
    hover,
    compact,
    rowClassName,
    cellClassName,
    selectable,
    isSelected,
    onSelect,
    renderCell,
    selectedAll = false,
}) => (
    <tr
        className={`${
            striped && rowIndex % 2 === 0 ? 'bg-gray-50' : 'bg-white'
        } ${hover ? 'hover:bg-gray-100' : ''} ${
            isSelected ? 'bg-blue-50' : ''
        } ${rowClassName}`}
    >
        {selectable && (
            <td className="px-6 py-4">
                <input
                    type="checkbox"
                    checked={selectedAll || isSelected}
                    onChange={(e) => onSelect(row, e.target.checked)}
                    className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
            </td>
        )}
        {columns.map((column, colIndex) => (
            <td
                key={colIndex}
                className={`px-6 whitespace-nowrap text-sm text-gray-900 ${
                    compact ? 'py-2' : 'py-4'
                } ${column.cellClassName || ''} ${cellClassName}`}
            >
                {renderCell(column, row, rowIndex)}
            </td>
        ))}
    </tr>
);

// Pagination Component
const TablePagination = ({
    paginationData,
    onPageChange,
    showPerPageOptions,
    perPageOptions,
    paginationClassName,
    gotoPage,
}) => {
    const [gotoPageNumber, setGotoPageNumber] = useState(1);

    const getPageNumbers = () => {
        const { current_page, last_page } = paginationData;
        const delta = 2;
        const range = [];
        const rangeWithDots = [];

        for (
            let i = Math.max(2, current_page - delta);
            i <= Math.min(last_page - 1, current_page + delta);
            i++
        ) {
            range.push(i);
        }

        if (current_page - delta > 2) {
            rangeWithDots.push(1, '...');
        } else {
            rangeWithDots.push(1);
        }

        rangeWithDots.push(...range);

        if (current_page + delta < last_page - 1) {
            rangeWithDots.push('...', last_page);
        } else {
            rangeWithDots.push(last_page);
        }

        return rangeWithDots.length === 1 ? [] : rangeWithDots;
    };

    const handleGotoPageChange = (e) => {
        if (e.target.value > paginationData.last_page) {
            setGotoPageNumber(paginationData.last_page);
        } else {
            setGotoPageNumber(e.target.value);
        }
    };

    const handleGotoPage = () => {
        onPageChange(gotoPageNumber, paginationData.per_page);
    };

    return (
        <div
            className={`px-4 py-3 border-t border-gray-200 ${paginationClassName}`}
        >
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div className="text-sm text-gray-700">
                    Showing {paginationData.from} to {paginationData.to} of{' '}
                    {paginationData.total} results
                </div>

                {showPerPageOptions && (
                    <div className="flex  items-center gap-2 text-sm">
                        <span>Show</span>
                        <select
                            value={paginationData?.per_page}
                            onChange={(e) =>
                                onPageChange(1, parseInt(e.target.value))
                            }
                            className="border border-gray-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            {perPageOptions.map((option) => (
                                <option key={option} value={option}>
                                    {option}
                                </option>
                            ))}
                        </select>
                        <span>entries</span>
                    </div>
                )}

                {gotoPage && (
                    <div className="flex items-center gap-1">
                        <input
                            type="number"
                            title="Enter the page number"
                            name="gotoPage"
                            id="gotoPage"
                            className="max-w-20 text-center p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            min={1}
                            max={paginationData.last_page}
                            value={gotoPageNumber}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    handleGotoPage();
                                }
                            }}
                            onChange={(e) => handleGotoPageChange(e)}
                        />
                        <button
                            onClick={() => handleGotoPage()}
                            className="p-2 text-white rounded-lg bg- text-sm bg-blue-500"
                        >
                            Go To Page
                        </button>
                    </div>
                )}

                <div className="flex items-center gap-1">
                    <button
                        onClick={() => onPageChange(1, paginationData.per_page)}
                        disabled={paginationData.current_page === 1}
                        className="p-2 text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg hover:bg-gray-100"
                    >
                        <ChevronsLeft className="h-4 w-4" />
                    </button>

                    <button
                        onClick={() =>
                            onPageChange(
                                paginationData.current_page - 1,
                                paginationData.per_page
                            )
                        }
                        disabled={paginationData.current_page === 1}
                        className="p-2 text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg hover:bg-gray-100"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </button>

                    {getPageNumbers().map((page, index) => (
                        <button
                            key={index}
                            onClick={() =>
                                typeof page === 'number' &&
                                onPageChange(page, paginationData.per_page)
                            }
                            disabled={page === '...'}
                            className={`px-3 py-1 text-sm rounded-lg ${
                                page === paginationData.current_page
                                    ? 'bg-blue-500 text-white'
                                    : page === '...'
                                    ? 'text-gray-400 cursor-default'
                                    : 'text-gray-700 hover:bg-gray-100'
                            } ${page === '...' ? 'cursor-default' : ''}`}
                        >
                            {page}
                        </button>
                    ))}

                    <button
                        onClick={() =>
                            onPageChange(
                                paginationData.current_page + 1,
                                paginationData.per_page
                            )
                        }
                        disabled={
                            paginationData.current_page ===
                            paginationData.last_page
                        }
                        className="p-2 text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg hover:bg-gray-100"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </button>

                    <button
                        onClick={() =>
                            onPageChange(
                                paginationData.last_page,
                                paginationData.per_page
                            )
                        }
                        disabled={
                            paginationData.current_page ===
                            paginationData.last_page
                        }
                        className="p-2 text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg hover:bg-gray-100"
                    >
                        <ChevronsRight className="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    );
};

const CPTable = ({
    data = {},
    columns = [],
    actionButtons = [],
    searchable = false,
    searchPlaceholder = 'Search...',
    filterable = false,
    filters = [],
    selectable = false,
    selectedAll = false,
    selectedItems = [],
    onSelectionChange = () => {},
    onPageChange = () => {},
    onSearch = () => {},
    onFilter = () => {},
    onSort = () => {},
    loading = false,
    className = '',
    tableClassName = '',
    headerClassName = '',
    bodyClassName = '',
    rowClassName = '',
    cellClassName = '',
    paginationClassName = '',
    showPagination = true,
    showPerPageOptions = true,
    gotoPage = false,
    perPageOptions = [10, 25, 50, 100],
    emptyMessage = 'No data available',
    loadingMessage = 'Loading...',
    sortable = false,
    striped = true,
    hover = true,
    bordered = true,
    compact = false,
    // toggleSelectAll,
}) => {
    const [searchTerm, setSearchTerm] = useState('');
    const [activeFilters, setActiveFilters] = useState({});
    const [sortConfig, setSortConfig] = useState({
        key: null,
        direction: 'asc',
    });

    const paginationData = useMemo(() => {
        if (!data || typeof data !== 'object') return null;

        return {
            current_page: data.current_page || 1,
            last_page: data.last_page || 1,
            per_page: data.per_page || 10,
            total: data.total || 0,
            from: data.from || 0,
            to: data.to || 0,
            data: data.data || [],
        };
    }, [data]);

    const tableData = paginationData?.data || [];

    // Handle search
    const handleSearch = (value) => {
        setSearchTerm(value);
        onSearch(value);
    };

    // Handle filter change
    const handleFilterChange = (filterKey, value) => {
        const newFilters = { ...activeFilters, [filterKey]: value };
        if (
            !value ||
            (typeof value === 'object' && Object.values(value).every((v) => !v))
        ) {
            delete newFilters[filterKey];
        }
        setActiveFilters(newFilters);
        onFilter(newFilters);
    };

    // Handle clear all filters
    const handleClearFilters = () => {
        setActiveFilters({});
        onFilter({});
    };

    // Handle sort
    const handleSort = (key) => {
        if (!sortable) return;

        let direction = 'asc';
        if (sortConfig.key === key && sortConfig.direction === 'asc') {
            direction = 'desc';
        }

        setSortConfig({ key, direction });
        onSort(key, direction);
    };

    // Handle selection
    // const handleSelectAll = () => {
    //     // const allIds = tableData.map((row) => row.id || row);
    //     // onSelectionChange(allIds);
    //     onselectionchange(selectableActions.selectAll, true);
    // };

    // const handleDeselectAll = () => {
    //     // onSelectionChange([]);
    //     onselectionchange(selectableActions.selectAll, false);
    // };

    // In CPTable main component:
    const handleRowSelect = (row, isChecked) => {
        onSelectionChange(selectableActions.selectRow, row.id || row);
    };

    // Render cell content
    const renderCell = (column, row, index) => {
        if (column.render) {
            return column.render(row, index);
        }

        if (column.accessor) {
            const value = column.accessor
                .split('.')
                .reduce((obj, key) => obj?.[key], row);
            return value;
        }

        return '';
    };

    return (
        <div className={`bg-white rounded-lg shadow-sm border ${className}`}>
            {/* {(searchable || filterable ) && ( */}
            <div className="p-4 border-b border-gray-200">
                <div className="flex flex-row justify-between gap-4">
                    <div className="flex flex-col w-full  sm:flex-row items-start sm:items-center gap-4">
                        {searchable && (
                            <TableSearch
                                searchTerm={searchTerm}
                                onSearch={handleSearch}
                                placeholder={searchPlaceholder}
                            />
                        )}

                        {selectable && selectedItems?.length > 0 && (
                            <SelectionControls
                                selectedAll={selectedAll}
                                onSelectionChange={onSelectionChange}
                                // toggleSelectAll={toggleSelectAll}
                            />
                        )}
                    </div>

                    <ActionButtons actionButtons={actionButtons} />

                    {filterable && filters.length > 0 && (
                        <AdvancedFilters
                            filters={filters}
                            activeFilters={activeFilters}
                            onFilterChange={handleFilterChange}
                            onClearFilters={handleClearFilters}
                        />
                    )}
                </div>
            </div>
            {/* )} */}

            <div className="overflow-x-auto rounded-md">
                <table className={`w-full ${tableClassName}`}>
                    <TableHeader
                        columns={columns}
                        sortConfig={sortConfig}
                        onSort={handleSort}
                        sortable={sortable}
                        selectable={selectable}
                        selectedItems={selectedItems}
                        tableData={tableData}
                        onSelectionChange={onSelectionChange}
                        selectedAll={selectedAll}
                    />
                    <tbody
                        className={`bg-white divide-y divide-gray-200 ${bodyClassName}`}
                    >
                        {loading ? (
                            <TableLoading
                                selectable={selectable}
                                columns={columns}
                                message={loadingMessage}
                            />
                        ) : tableData.length === 0 ? (
                            <TableEmptyState
                                columns={
                                    selectable
                                        ? columns.length + 1
                                        : columns.length
                                }
                                message={emptyMessage}
                            />
                        ) : (
                            tableData.map((row, rowIndex) => (
                                <TableRow
                                    key={rowIndex}
                                    row={row}
                                    columns={columns}
                                    rowIndex={rowIndex}
                                    striped={striped}
                                    hover={hover}
                                    compact={compact}
                                    rowClassName={rowClassName}
                                    cellClassName={cellClassName}
                                    selectable={selectable}
                                    isSelected={selectedItems.includes(
                                        row.id || row
                                    )}
                                    onSelect={handleRowSelect}
                                    renderCell={renderCell}
                                    selectedAll={selectedAll}
                                />
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {showPagination &&
                paginationData &&
                paginationData.last_page > 1 && (
                    <TablePagination
                        paginationData={paginationData}
                        onPageChange={onPageChange}
                        showPerPageOptions={showPerPageOptions}
                        perPageOptions={perPageOptions}
                        paginationClassName={paginationClassName}
                        gotoPage={gotoPage}
                    />
                )}
        </div>
    );
};

// Enhanced Demo Component
// const DemoCPTable = () => {
//     const [currentPage, setCurrentPage] = useState(1);
//     const [perPage, setPerPage] = useState(10);
//     const [search, setSearch] = useState('');
//     const [filters, setFilters] = useState({});
//     const [sort, setSort] = useState({ key: null, direction: 'asc' });
//     const [selectedItems, setSelectedItems] = useState([]);

//     // Sample data
//     const sampleData = {
//         current_page: currentPage,
//         last_page: 5,
//         per_page: perPage,
//         total: 47,
//         from: (currentPage - 1) * perPage + 1,
//         to: Math.min(currentPage * perPage, 47),
//         data: [
//             {
//                 id: 1,
//                 name: 'John Doe',
//                 email: 'john@example.com',
//                 role: 'Admin',
//                 status: 'Active',
//                 created_at: '2024-01-15',
//                 department: 'IT',
//             },
//             {
//                 id: 2,
//                 name: 'Jane Smith',
//                 email: 'jane@example.com',
//                 role: 'User',
//                 status: 'Active',
//                 created_at: '2024-01-16',
//                 department: 'HR',
//             },
//             {
//                 id: 3,
//                 name: 'Bob Johnson',
//                 email: 'bob@example.com',
//                 role: 'Editor',
//                 status: 'Inactive',
//                 created_at: '2024-01-17',
//                 department: 'Marketing',
//             },
//             {
//                 id: 4,
//                 name: 'Alice Brown',
//                 email: 'alice@example.com',
//                 role: 'User',
//                 status: 'Active',
//                 created_at: '2024-01-18',
//                 department: 'Finance',
//             },
//             {
//                 id: 5,
//                 name: 'Charlie Wilson',
//                 email: 'charlie@example.com',
//                 role: 'Admin',
//                 status: 'Active',
//                 created_at: '2024-01-19',
//                 department: 'IT',
//             },
//             {
//                 id: 6,
//                 name: 'Diana Davis',
//                 email: 'diana@example.com',
//                 role: 'Editor',
//                 status: 'Inactive',
//                 created_at: '2024-01-20',
//                 department: 'HR',
//             },
//             {
//                 id: 7,
//                 name: 'Eve Miller',
//                 email: 'eve@example.com',
//                 role: 'User',
//                 status: 'Active',
//                 created_at: '2024-01-21',
//                 department: 'Marketing',
//             },
//             {
//                 id: 8,
//                 name: 'Frank Garcia',
//                 email: 'frank@example.com',
//                 role: 'User',
//                 status: 'Active',
//                 created_at: '2024-01-22',
//                 department: 'Finance',
//             },
//             {
//                 id: 9,
//                 name: 'Grace Martinez',
//                 email: 'grace@example.com',
//                 role: 'Editor',
//                 status: 'Active',
//                 created_at: '2024-01-23',
//                 department: 'IT',
//             },
//             {
//                 id: 10,
//                 name: 'Henry Taylor',
//                 email: 'henry@example.com',
//                 role: 'Admin',
//                 status: 'Inactive',
//                 created_at: '2024-01-24',
//                 department: 'HR',
//             },
//         ],
//     };

//     const columns = [
//         {
//             header: 'ID',
//             accessor: 'id',
//             sortable: true,
//             cellClassName: 'font-medium',
//         },
//         {
//             header: 'Name',
//             accessor: 'name',
//             sortable: true,
//             render: (row) => (
//                 <div className="flex items-center">
//                     <div className="h-8 w-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
//                         {row.name.charAt(0)}
//                     </div>
//                     <span className="font-medium">{row.name}</span>
//                 </div>
//             ),
//         },
//         {
//             header: 'Email',
//             accessor: 'email',
//             sortable: true,
//             render: (row) => (
//                 <a
//                     href={`mailto:${row.email}`}
//                     className="text-blue-600 hover:text-blue-800"
//                 >
//                     {row.email}
//                 </a>
//             ),
//         },
//         {
//             header: 'Role',
//             accessor: 'role',
//             sortable: true,
//             render: (row) => (
//                 <span
//                     className={`px-2 py-1 text-xs font-medium rounded-full ${
//                         row.role === 'Admin'
//                             ? 'bg-purple-100 text-purple-800'
//                             : row.role === 'Editor'
//                             ? 'bg-blue-100 text-blue-800'
//                             : 'bg-gray-100 text-gray-800'
//                     }`}
//                 >
//                     {row.role}
//                 </span>
//             ),
//         },
//         {
//             header: 'Status',
//             accessor: 'status',
//             sortable: true,
//             render: (row) => (
//                 <span
//                     className={`px-2 py-1 text-xs font-medium rounded-full ${
//                         row.status === 'Active'
//                             ? 'bg-green-100 text-green-800'
//                             : 'bg-red-100 text-red-800'
//                     }`}
//                 >
//                     {row.status}
//                 </span>
//             ),
//         },
//         {
//             header: 'Department',
//             accessor: 'department',
//             sortable: true,
//         },
//         {
//             header: 'Created',
//             accessor: 'created_at',
//             sortable: true,
//             render: (row) => new Date(row.created_at).toLocaleDateString(),
//         },
//         {
//             header: 'Actions',
//             render: (row) => (
//                 <div className="flex gap-2">
//                     <button className="text-blue-600 hover:text-blue-800 text-sm">
//                         Edit
//                     </button>
//                     <button className="text-red-600 hover:text-red-800 text-sm">
//                         Delete
//                     </button>
//                 </div>
//             ),
//         },
//     ];

//     const filterConfig = [
//         {
//             title: 'Basic Filters',
//             filters: [
//                 {
//                     key: 'role',
//                     label: 'Role',
//                     type: 'select',
//                     placeholder: 'Filter by role',
//                     options: [
//                         { value: 'Admin', label: 'Admin' },
//                         { value: 'Editor', label: 'Editor' },
//                         { value: 'User', label: 'User' },
//                     ],
//                 },
//                 {
//                     key: 'status',
//                     label: 'Status',
//                     type: 'select',
//                     placeholder: 'Filter by status',
//                     options: [
//                         { value: 'Active', label: 'Active' },
//                         { value: 'Inactive', label: 'Inactive' },
//                     ],
//                 },
//                 {
//                     key: 'department',
//                     label: 'Department',
//                     type: 'select',
//                     placeholder: 'Filter by department',
//                     options: [
//                         { value: 'IT', label: 'IT' },
//                         { value: 'HR', label: 'HR' },
//                         { value: 'Marketing', label: 'Marketing' },
//                         { value: 'Finance', label: 'Finance' },
//                     ],
//                 },
//             ],
//         },
//         {
//             title: 'Date Filters',
//             filters: [
//                 {
//                     key: 'created_date',
//                     label: 'Created Date',
//                     type: 'daterange',
//                     placeholder: 'Filter by creation date',
//                 },
//                 {
//                     key: 'specific_date',
//                     label: 'Specific Date',
//                     type: 'date',
//                     placeholder: 'Select specific date',
//                 },
//             ],
//         },
//         {
//             title: 'Advanced Filters',
//             filters: [
//                 {
//                     key: 'name_search',
//                     label: 'Name Contains',
//                     type: 'text',
//                     placeholder: 'Search in names',
//                 },
//                 {
//                     key: 'email_domain',
//                     label: 'Email Domain',
//                     type: 'text',
//                     placeholder: 'e.g., @example.com',
//                 },
//                 {
//                     key: 'user_id',
//                     label: 'User ID',
//                     type: 'number',
//                     placeholder: 'Enter user ID',
//                 },
//             ],
//         },
//     ];

//     const handlePageChange = (page, newPerPage = perPage) => {
//         setCurrentPage(page);
//         if (newPerPage !== perPage) {
//             setPerPage(newPerPage);
//         }
//         console.log('Page changed:', {
//             page,
//             perPage: newPerPage,
//             search,
//             filters,
//             sort,
//         });
//     };

//     const handleSearch = (searchTerm) => {
//         setSearch(searchTerm);
//         setCurrentPage(1);
//         console.log('Search:', searchTerm);
//     };

//     const handleFilter = (filterValues) => {
//         setFilters(filterValues);
//         setCurrentPage(1);
//         console.log('Filters:', filterValues);
//     };

//     const handleSort = (key, direction) => {
//         setSort({ key, direction });
//         console.log('Sort:', { key, direction });
//     };

//     const handleSelectionChange = (selectedIds) => {
//         setSelectedItems(selectedIds);
//         console.log('Selected items:', selectedIds);
//     };

//     return (
//         <div className=" mx-auto">
//             <div className="mb-6">
//                 <h1 className="text-2xl font-bold text-gray-900">
//                     Enhanced User Management
//                 </h1>
//                 <p className="text-gray-600">
//                     Manage users with advanced filtering, selection, and
//                     pagination
//                 </p>
//             </div>

//             <CPTable
//                 data={sampleData}
//                 columns={columns}
//                 searchable={true}
//                 searchPlaceholder="Search users..."
//                 filterable={true}
//                 filters={filterConfig}
//                 sortable={true}
//                 selectable={true}
//                 selectedItems={selectedItems}
//                 onSelectionChange={handleSelectionChange}
//                 onPageChange={handlePageChange}
//                 onSearch={handleSearch}
//                 onFilter={handleFilter}
//                 onSort={handleSort}
//                 className="shadow-lg"
//                 striped={true}
//                 hover={true}
//                 bordered={true}
//             />
//         </div>
//     );
// };

// export default DemoCPTable;
export default CPTable;
