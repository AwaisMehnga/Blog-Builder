import { ChevronLeft } from 'lucide-react'
import React from 'react'
import { Link } from 'react-router-dom'

export default function GoBack({goto, text}) {
  return (
    <Link 
      to={goto} 
      className="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4"
    >
      <ChevronLeft className="mr-2 w-4 h-4" />
      {text}
    </Link>
  )
}