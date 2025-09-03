import React from "react";
import { createRoot } from "react-dom/client";
import NotFound from "../UI/pages/NotFound";
import "./notfound.css";

function NotfoundApp() {
  return (
      <NotFound />
  );
}

// Mount React app
const rootElement = document.getElementById("app");
if (rootElement) {
  createRoot(rootElement).render(<NotfoundApp />);
}
