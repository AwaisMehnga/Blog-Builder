import React from 'react';
import { createRoot } from 'react-dom/client';
import './homepage.css';

function App() {
    return (
        <div className="bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 min-h-screen flex items-center justify-center">
            <div className="max-w-2xl mx-auto text-center space-y-8 p-8">
                <h1 className="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-yellow-400 animate-pulse">
                    Welcome 
                </h1>
                <p className="text-xl text-gray-300 leading-relaxed border-l-4 border-cyan-400 pl-6 italic text">
                    This React app is loaded only for the Home Page 
                </p>
                <div className="w-32 h-1 bg-gradient-to-r from-pink-500 to-violet-500 mx-auto rounded-full"></div>
            </div>
        </div>
    );
}

// Mount React app
const rootElement = document.getElementById('app');
if (rootElement) {
    createRoot(rootElement).render(<App />);
}
