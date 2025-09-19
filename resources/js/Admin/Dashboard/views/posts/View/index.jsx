import React, { useEffect, useMemo, useCallback } from "react";
import CPTable from "../../../../../UI/CPTable";
import { Link } from "react-router-dom";
import { formatDateTime } from "@Functions/FormatDateTime";
import { usePostViewStore } from "../Stores/usePostViewStore";
import { actions } from "../Actions";
import { debounce } from "lodash";
import Badge from "../../../../../UI/Badge";
import { Rss } from "lucide-react";

export default function View() {
  const { posts, loading, page, per_page, search, setPage, setPerPage, setSearch } = usePostViewStore();

  useEffect(() => {
    actions.postView.getPost(page, per_page, search);
  }, []);

  const debouncedSearch = useMemo(
    () =>
      debounce((newSearch) => {
        actions.postView.getPost(1, per_page, newSearch);
      }, 500),
    [per_page] 
  );

  const handleSearch = useCallback(
    (newSearch) => {
      setSearch(newSearch);
      debouncedSearch(newSearch);
    },
    [debouncedSearch, setSearch]
  );

  const handlePageChange = useCallback(
    (newPage, newPerPage) => {
      setPage(newPage);
      setPerPage(newPerPage);
      actions.postView.getPost(newPage, newPerPage, search);
    },
    [setPage, setPerPage, search]
  );

  useEffect(() => {
    return () => {
      debouncedSearch.cancel();
    };
  }, [debouncedSearch]);

  const columns = [
    {
      header: "Title",
      render: (row) => <Link to={`/post/create/${row.id}`}>{row?.title}</Link>,
    },
    {
      header: "Status",
      render: (row) => <Badge variant={row?.status === "published" ? "success" : "warning"} size="sm">{row?.status}</Badge>,
    },
    {
      header: "Created At",
      render: (row) => <span>{formatDateTime(row?.created_at)}</span>,
    },
    {
      header: "Updated At",
      render: (row) => <span>{formatDateTime(row?.updated_at)}</span>,
    },
  ];

  const actionButtons = [
    {
      id:1,
      innerText: <span className="flex items-center gap-2"><Rss className="w-4 h-4" /> Publish All</span>,
      onClick: () => actions.postView.bulk('all', 'publish'),
      disabled: loading ,
    },
    {
      id:2,
      innerText: <span className="flex items-center gap-2"><Rss className="w-4 h-4" /> Move to Draft</span>,
      onClick: () => actions.postView.bulk('all', 'draft'),
      disabled: loading,
    }
  ]

  return (
    <CPTable
      columns={columns}
      data={posts?.data || []}
      loading={loading}
      searchable
      showPerPageOptions={false}
      onPageChange={handlePageChange}
      onSearch={handleSearch}
      actionButtons={actionButtons}
      gotoPage
    />
  );
}
