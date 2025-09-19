import React, { Suspense } from "react";
import { createRoot } from "react-dom/client";
import { Toaster } from "react-hot-toast";
import { BrowserRouter } from "react-router-dom";
import AppRouter from "./AppRouter";
import "./dashboard.css";
import Loader from "../../UI/pages/Loader";

function AdminDashboardApp() {
  return (
    <BrowserRouter basename="/admin/awais-mehnga/dashboard">
      <Suspense fallback={<Loader />}>
        <Toaster position="top-right" reverseOrder={false} />
        <AppRouter />
        <Toaster position="top-right" reverseOrder={false} />
      </Suspense>
    </BrowserRouter>
  );
}

// Mount React app
const rootElement = document.getElementById("app");
if (rootElement) {
  createRoot(rootElement).render(<AdminDashboardApp />);
}
