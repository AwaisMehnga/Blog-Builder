export default function Loader() {
  return (
    <div className="flex items-center justify-center min-h-screen bg-base-100">
      <div className="relative flex items-center justify-center">
        {/* Invoice sheet */}
        <div className="w-16 h-20 bg-base-200 rounded-md shadow-md animate-bounce"></div>

        {/* Spinner ring behind the invoice */}
        <span className="loading loading-ring loading-lg text-primary absolute -top-6 -left-6"></span>
      </div>
    </div>
  );
}
