import React from "react";
import { Outlet, useLocation } from "react-router-dom";
import Sidebar from "./Sidebar";
import BreadCrumbs from "../../UI/BreadCrumbs";

export default function Layout() {
  const location = useLocation();
  
  // Generate breadcrumbs from current path
  const generateBreadcrumbs = () => {
    const pathSegments = location.pathname.split('/').filter(Boolean);
    
    return pathSegments.map((segment, index) => {
      const path = '/' + pathSegments.slice(0, index + 1).join('/');
      const isLast = index === pathSegments.length - 1;
      
      // Capitalize and format segment name
      const title = segment.charAt(0).toUpperCase() + segment.slice(1).replace('-', ' ');
      
      return {
        title,
        link: path,
        active: isLast
      };
    });
  };

  const breadcrumbs = generateBreadcrumbs();




  return (
    <div className="flex w-full h-screen">
      {/* Sidebar */}
      <Sidebar />

      {/* Main Content */}
      <div className="flex-1 overflow-y-auto bg-gray-50 p-6 space-y-4">
        {/* Breadcrumbs */}
        <BreadCrumbs breadcrumbs={breadcrumbs}/>
        <Outlet />
      </div>
    </div>
  );
}
