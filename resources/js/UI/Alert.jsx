import React, { useState, useRef } from "react";
import { CircleAlert, CircleCheck, CircleX, Info } from "lucide-react";

export function AlertComponent({ alert }) {
  if (!alert.show) return null;

  const getAlertClass = (type) => {
    switch (type) {
      case "success":
        return "alert-success";
      case "error":
        return "alert-error";
      case "warning":
        return "alert-warning";
      default:
        return "alert-info";
    }
  };

  const getIcon = (type) => {
    switch (type) {
      case "success":
        return <CircleCheck className="w-6 h-6" />;
      case "error":
        return <CircleX className="w-6 h-6" />;
      case "warning":
        return <CircleAlert className="w-6 h-6" />;
      default:
        return <Info className="w-6 h-6" />;
    }
  };

  return (
    <div
      role="alert"
      className={`alert flex items-center gap-2 p-3 rounded-md ${getAlertClass(
        alert.type
      )}`}
    >
      {getIcon(alert.type)}
      <span>{alert.message}</span>
    </div>
  );
}

export function useAlert() {
  const [alert, setAlert] = useState({
    show: false,
    message: "",
    type: "info",
  });

  const timeoutRef = useRef(null);

  const handleAlert = (message, type = "info") => {
    // Clear any existing timeout so previous alert doesn't auto-hide early
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }

    setAlert({ show: true, message, type });

    timeoutRef.current = setTimeout(() => {
      setAlert({ show: false, message: "", type: "info" });
      timeoutRef.current = null;
    }, 10000); // auto hide after 10s
  };

  const Alert = <AlertComponent alert={alert} />;

  return { handleAlert, Alert };
}
