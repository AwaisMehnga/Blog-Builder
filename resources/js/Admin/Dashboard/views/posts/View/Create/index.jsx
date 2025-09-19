import React from 'react'
import { useParams } from 'react-router-dom'

export default function Create() {
  const params = useParams();
  
  console.log("Create Component Params:", params);
  return (
    <div>{params.blogId ? `Edit Post: ${params.blogId}` : "Create New Post"}</div>
  )
}
