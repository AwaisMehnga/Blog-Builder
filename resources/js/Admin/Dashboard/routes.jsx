import React, { lazy } from "react";
import Layout from "./Layout";

const Analytics = lazy(() => import("./views/Analytics"));

// Posts and related views
const PostLayout = lazy(() => import("./views/posts/PostLayout"));
const ListPost = lazy(() => import("./views/posts/View"));
const CreatePost = lazy(() => import("./views/posts/View/Create"));
const Category = lazy(() => import("./views/posts/Category"));

// 404 Page
const NotFound = lazy(() => import("../../UI/pages/NotFound"));

export const routes = [
  {
    path: "/",
    element: <Layout />,
    children: [
      {
        index: true, 
        element: <Analytics />,
      },
      {
        path: "analytics",
        element: <Analytics />,
      },
      {
        path: "post",
        element: <PostLayout />,
        children: [
          {
            index: true,
            element: <ListPost />,
          },
          {
            path: "category",
            element: <Category />,
          },
          {
            path: "create/:blogId?",
            element: <CreatePost />,
          },
        ],
      },
      {
        path: "settings",
        element: <div>Settings Page</div>,
      },
    ],
  },
  {
        path: "*",
        element: <NotFound />,
      }
];
