import React, { useState, useRef, useEffect } from "react";
import { NavLink } from "react-router-dom";
import {
  BarChart2,
  Home,
  Settings,
  ChevronDown,
  ChevronRight,
  ChevronLeft,
} from "lucide-react";

export default function Sidebar() {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [popupMenu, setPopupMenu] = useState(null); // { item, position }
  const [hovering, setHovering] = useState(false);

  const menuItems = [
    { name: "Dashboard", path: "/", icon: <Home size={18} /> },
    {
      name: "Post",
      path: "/post",
      icon: <Home size={18} />,
      children: [
        { name: "Categories", path: "/post/category" },
        { name: "Create Post", path: "/post/create" }
      ],
    },
    { name: "Analytics", path: "/analytics", icon: <BarChart2 size={18} /> },
    {
      name: "Settings",
      path: "/settings",
      icon: <Settings size={18} />,
      children: [
        { name: "Profile", path: "/settings/profile" },
        { name: "Security", path: "/settings/security" },
      ],
    },
  ];

  /** Close popup when not hovering parent or popup */
  useEffect(() => {
    if (!hovering) {
      const timeout = setTimeout(() => setPopupMenu(null), 150);
      return () => clearTimeout(timeout);
    }
  }, [hovering]);

  return (
    <div className="relative flex">
      {/* Sidebar */}
      <div
        className={`bg-white h-screen shadow-md flex flex-col transition-all duration-300 ${
          sidebarOpen ? "w-56" : "w-16"
        }`}
      >
        {/* Header */}
        <div className="p-3 border-b">
          <h1
            className={`text-lg font-semibold text-gray-800 transition-all duration-300 overflow-hidden ${
              sidebarOpen ? "opacity-100 w-auto" : "opacity-0 w-0"
            }`}
          >
            BilloCraft
          </h1>
        </div>

        {/* Menu */}
        <nav className="flex-1 p-1 overflow-y-auto">
          <MenuList
            items={menuItems}
            sidebarOpen={sidebarOpen}
            setPopupMenu={setPopupMenu}
            setHovering={setHovering}
          />
        </nav>

        {/* Footer Collapse Button */}
        <div className="border-t p-2">
          <div
            className={`flex transition-all duration-300 ${
              sidebarOpen ? "justify-end" : "justify-center"
            }`}
          >
            <button
              className="w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100"
              onClick={() => {
                setSidebarOpen((prev) => !prev);
                setPopupMenu(null); // close popup when toggling
              }}
            >
              {sidebarOpen ? <ChevronLeft size={18} /> : <ChevronRight size={18} />}
            </button>
          </div>
        </div>
      </div>

      {/* Floating Popup */}
      {!sidebarOpen && popupMenu && (
        <PopupMenu
          item={popupMenu.item}
          position={popupMenu.position}
          setHovering={setHovering}
        />
      )}
    </div>
  );
}

function MenuList({ items, sidebarOpen, setPopupMenu, setHovering, depth = 0 }) {
  return (
    <div className={`${depth > 0 ? "pl-3" : ""}`}>
      {items.map((item) => (
        <MenuItem
          key={item.path}
          item={item}
          sidebarOpen={sidebarOpen}
          setPopupMenu={setPopupMenu}
          setHovering={setHovering}
        />
      ))}
    </div>
  );
}

function MenuItem({ item, sidebarOpen, setPopupMenu, setHovering }) {
  const [open, setOpen] = useState(false);
  const itemRef = useRef(null);
  const hasChildren = item.children && item.children.length > 0;

  const handleMouseEnter = () => {
    if (!sidebarOpen && hasChildren && itemRef.current) {
      const rect = itemRef.current.getBoundingClientRect();
      setPopupMenu({
        item,
        position: {
          top: rect.top,
          left: rect.right,
        },
      });
      setHovering(true);
    }
  };

  const handleMouseLeave = () => {
    if (!sidebarOpen && hasChildren) {
      setHovering(false);
    }
  };

  return (
    <div
      className="relative"
      ref={itemRef}
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
    >
      <div className="flex items-center">
        <NavLink
          to={item.path}
          className={({ isActive }) =>
            `flex items-center flex-1 gap-2 p-2 rounded-md mb-1 transition-all ${
              isActive
                ? "bg-blue-100 text-blue-600 font-medium"
                : "text-gray-700 hover:bg-gray-100"
            }`
          }
        >
          {/* Icon */}
          <div className="w-8 h-8 flex items-center justify-center rounded">
            {item.icon}
          </div>

          {/* Label (only visible when expanded) */}
          {sidebarOpen && (
            <span className="whitespace-nowrap text-sm">{item.name}</span>
          )}
        </NavLink>

        {/* Expand button when sidebar is expanded */}
        {hasChildren && sidebarOpen && (
          <button
            className="ml-1 w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100"
            onClick={(e) => {
              e.stopPropagation();
              setOpen(!open);
            }}
          >
            {open ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
          </button>
        )}
      </div>

      {/* Children when expanded */}
      {hasChildren && open && sidebarOpen && (
        <div className="pl-3 border-l border-gray-200">
          <MenuList
            items={item.children}
            sidebarOpen={sidebarOpen}
            setPopupMenu={setPopupMenu}
            setHovering={setHovering}
          />
        </div>
      )}
    </div>
  );
}

function PopupMenu({ item, position, setHovering }) {
  const [nestedOpen, setNestedOpen] = useState(null);

  return (
    <div
      className="absolute bg-white shadow-lg rounded-md p-2 min-w-[180px] border z-50"
      style={{
        top: position.top,
        left: position.left + 4,
      }}
      onMouseEnter={() => setHovering(true)}
      onMouseLeave={() => setHovering(false)}
    >
      <div className="font-semibold text-gray-700 mb-1">{item.name}</div>
      {item.children.map((child) => {
        const hasSubChildren = child.children && child.children.length > 0;
        return (
          <div key={child.path} className="relative">
            <NavLink
              to={child.path}
              className="flex justify-between items-center px-2 py-1 rounded hover:bg-gray-100 text-sm text-gray-700"
            >
              {child.name}
              {hasSubChildren && (
                <button
                  className="ml-2"
                  onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    setNestedOpen(nestedOpen === child.path ? null : child.path);
                  }}
                >
                  {nestedOpen === child.path ? (
                    <ChevronDown size={12} />
                  ) : (
                    <ChevronRight size={12} />
                  )}
                </button>
              )}
            </NavLink>

            {/* Nested children inside popup */}
            {hasSubChildren && nestedOpen === child.path && (
              <div className="ml-3 mt-1 border-l border-gray-200 pl-2">
                {child.children.map((subChild) => (
                  <NavLink
                    key={subChild.path}
                    to={subChild.path}
                    className="block px-2 py-1 rounded hover:bg-gray-100 text-sm text-gray-600"
                  >
                    {subChild.name}
                  </NavLink>
                ))}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}
