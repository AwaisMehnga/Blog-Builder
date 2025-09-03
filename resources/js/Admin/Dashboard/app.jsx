import React from "react";
import { createRoot } from "react-dom/client";

function AdminDashboardApp() {
  return (
    <div className="container">
      <h1>Welcome to Admin Dashboard</h1>
      <p>This is a React component!</p>
    </div>
  );
}

// Mount React app
const rootElement = document.getElementById("app");
if (rootElement) {
  createRoot(rootElement).render(<AdminDashboardApp />);
}