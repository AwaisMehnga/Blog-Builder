export default function NotFound() {
  return (
    <div className="flex flex-col items-center justify-center min-h-screen text-center px-6">
      {/* Animated 404 */}
      <h1 className="mt-6 text-9xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary ">
        404
      </h1>

      {/* Title */}
      <h2 className="mt-4 text-3xl font-bold text-base-content">
        Oops! Page Not Found
      </h2>

      {/* Subtitle */}
      <p className="mt-2 text-base text-base-content/70 max-w-md leading-relaxed">
        The page you’re looking for doesn’t exist. It might have been removed,
        renamed, or is temporarily unavailable.
      </p>

      {/* Actions */}
      <div className="mt-8 flex flex-wrap justify-center gap-4">
        {
          <a
            href="/"
            className="btn btn-primary btn-wide shadow-md hover:scale-105 transition-transform"
          >
            Go Home
          </a>
        }
        <a
          href="/contact"
          className="btn btn-outline btn-secondary btn-wide hover:scale-105 transition-transform"
        >
          Contact Support
        </a>
      </div>
    </div>
  );
}
