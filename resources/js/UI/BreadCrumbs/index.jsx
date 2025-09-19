import React from 'react';
import { ChevronRight, Home } from 'lucide-react';
import { Link } from 'react-router-dom';
import PropTypes from 'prop-types';

const Breadcrumbs = ({ breadcrumbs = [], className = '' }) => {
    return (
        <nav className={`flex ${className}`} aria-label="Breadcrumb">
            <ol className="inline-flex items-center m-0 space-x-1 list-none">
                {/* Home breadcrumb */}
                <li className="inline-flex items-center">
                    <Link
                        to="/"
                        className="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600"
                    >
                        <Home className="w-4 h-4 mr-2" />
                        Home
                    </Link>
                </li>

                {breadcrumbs.map((breadcrumb, index) => (
                    <li key={index}>
                        <div className="flex items-center">
                            <ChevronRight className="w-4 h-4 text-gray-400" />
                            {breadcrumb.active ? (
                                <span
                                    className="ml-1 text-sm font-medium text-gray-500 md:ml-2 truncate max-w-[40ch]"
                                    aria-current="page"
                                >
                                    {breadcrumb.title}
                                </span>
                            ) : (
                                <Link
                                    to={breadcrumb.link}
                                    className="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2"
                                >
                                    {breadcrumb.title}
                                </Link>
                            )}
                        </div>
                    </li>
                ))}
            </ol>
        </nav>
    );
};

export default React.memo(Breadcrumbs);

Breadcrumbs.propTypes = {
    breadcrumbs: PropTypes.arrayOf(
        PropTypes.shape({
            title: PropTypes.string.isRequired,
            link: PropTypes.string,
            active: PropTypes.bool,
        })
    ),
};