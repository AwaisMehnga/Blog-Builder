import React from "react";
import PropTypes from "prop-types";
import classnames from "classnames";

/**
 * Badge component
 *
 * @param {string} variant - The visual style of the badge (default, success, warning, error, info)
 * @param {string} size - Badge size (sm, md, lg)
 * @param {string} shape - Shape of the badge ('rounded' or 'square')
 * @param {ReactNode} leftIcon - Icon to show on the left
 * @param {ReactNode} rightIcon - Icon to show on the right
 * @param {function} onClick - Optional click handler
 * @param {boolean} isClosable - If true, shows a close button
 * @param {string} className - Additional Tailwind classes
 * @param {ReactNode} children - Badge text or elements
 */
export default function Badge({
  variant = "default",
  size = "md",
  shape = "rounded",
  leftIcon,
  rightIcon,
  onClick,
  isClosable = false,
  className = "",
  children,
}) {
  const baseStyles =
    "inline-flex items-center justify-center font-medium transition-colors select-none h-fit";

  // Variants
  const variantStyles = {
    default: "bg-gray-100 text-gray-800",
    success: "bg-green-100 text-green-800",
    warning: "bg-yellow-100 text-yellow-800",
    error: "bg-red-100 text-red-800",
    info: "bg-blue-100 text-blue-800",
  };

  // Sizes with balanced vertical padding
  const sizeStyles = {
    sm: "text-xs px-2 py-0.5 min-h-[20px]",
    md: "text-sm px-3 py-1 min-h-[24px]",
    lg: "text-base px-4 py-1.5 min-h-[28px]",
  };

  // Shape
  const shapeStyles = {
    rounded: "rounded-full",
    square: "rounded-md",
  };

  return (
    <div
      className={classnames(
        "flex items-center justify-center",
        baseStyles,
        variantStyles[variant],
        sizeStyles[size],
        shapeStyles[shape],
        className
      )}
      onClick={onClick}
      role={onClick ? "button" : undefined}
    >
      {leftIcon && (
        <span className="mr-1 flex items-center justify-center">
          {leftIcon}
        </span>
      )}

      <span className="flex items-center justify-center">{children}</span>

      {rightIcon && (
        <span className="ml-1 flex items-center justify-center">
          {rightIcon}
        </span>
      )}

      {isClosable && (
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            onClick && onClick(e);
          }}
          className="ml-1 text-gray-500 hover:text-gray-700 focus:outline-none flex items-center justify-center"
        >
          ✕
        </button>
      )}
    </div>
  );
}

Badge.propTypes = {
  variant: PropTypes.oneOf(["default", "success", "warning", "error", "info"]),
  size: PropTypes.oneOf(["sm", "md", "lg"]),
  shape: PropTypes.oneOf(["rounded", "square"]),
  leftIcon: PropTypes.node,
  rightIcon: PropTypes.node,
  onClick: PropTypes.func,
  isClosable: PropTypes.bool,
  className: PropTypes.string,
  children: PropTypes.node.isRequired,
};
