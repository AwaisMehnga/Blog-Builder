# Blog Admin API Documentation

## Overview

This is a comprehensive admin API for managing your blog system, similar to WordPress admin functionality. All endpoints are protected by admin authentication middleware.

**Base URL:** `/api/v1`
**Authentication:** Admin session required (handled by AdminMiddleware)

## Dashboard & Analytics

### Get Dashboard Statistics
```http
GET /api/v1/dashboard/stats
```

Returns comprehensive dashboard statistics including:
- Blog counts by status (published, draft, archived)
- Category and tag counts
- Recent blogs
- Popular categories
- Status distribution for charts
- Monthly creation statistics

**Response:**
```json
{
  "success": true,
  "data": {
    "counts": {
      "blogs": {
        "total": 50,
        "published": 35,
        "draft": 10,
        "archived": 5
      },
      "categories": 8,
      "tags": 25
    },
    "recent_blogs": [...],
    "popular_categories": [...],
    "status_distribution": {...},
    "monthly_stats": [...]
  }
}
```

### Get Analytics
```http
GET /api/v1/dashboard/analytics?days=30
```

**Query Parameters:**
- `days` (optional): Number of days for analytics (7-90, default: 30)

Returns detailed analytics including content creation trends, category distribution, top tags, and content metrics.

### Get Recent Activity
```http
GET /api/v1/dashboard/activity?limit=20
```

**Query Parameters:**
- `limit` (optional): Number of activities to return (5-50, default: 20)

### Global Search
```http
GET /api/v1/search?q=search_term&type=all&limit=10
```

**Query Parameters:**
- `q`: Search query
- `type` (optional): Search type (all, blogs, categories, tags, default: all)
- `limit` (optional): Results limit (5-50, default: 10)

## Blog Management

### List Blogs
```http
GET /api/v1/blogs?page=1&limit=10&status=published&category_id=1&search=keyword
```

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (1-50, default: 10)
- `status` (optional): Filter by status (draft, published, archived)
- `category_id` (optional): Filter by category ID
- `search` (optional): Search in title and content

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Sample Blog Post",
      "slug": "sample-blog-post",
      "content": "Blog content here...",
      "excerpt": "Short excerpt...",
      "status": "published",
      "category_id": 1,
      "published_at": "2024-01-01 12:00:00",
      "created_at": "2024-01-01 10:00:00",
      "updated_at": "2024-01-01 12:00:00",
      "category": {
        "id": 1,
        "name": "Technology",
        "slug": "technology"
      },
      "tags": [
        {
          "id": 1,
          "name": "PHP",
          "slug": "php"
        }
      ],
      "seo_meta": {
        "meta_title": "Custom SEO Title",
        "meta_description": "SEO description..."
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "total_pages": 5,
    "has_next": true,
    "has_prev": false
  }
}
```

### Get Single Blog
```http
GET /api/v1/blogs/{id}
```

Returns a single blog with all related data (category, tags, SEO meta).

### Create Blog
```http
POST /api/v1/blogs
```

**Request Body:**
```json
{
  "title": "New Blog Post",
  "slug": "new-blog-post",
  "content": "Full blog content here...",
  "excerpt": "Short excerpt...",
  "status": "draft",
  "category_id": 1,
  "published_at": "2024-01-01 12:00:00",
  "tags": [
    {"id": 1},
    {"name": "New Tag"}
  ],
  "seo_meta": {
    "meta_title": "SEO Title",
    "meta_description": "SEO Description",
    "meta_keywords": "keyword1, keyword2",
    "og_title": "OG Title",
    "og_description": "OG Description",
    "og_image": "/uploads/images/og-image.jpg",
    "twitter_title": "Twitter Title",
    "twitter_description": "Twitter Description",
    "twitter_image": "/uploads/images/twitter-image.jpg",
    "canonical_url": "https://yourdomain.com/blog/new-blog-post"
  }
}
```

**Field Descriptions:**
- `title`: Blog title (required)
- `slug`: URL slug (auto-generated if not provided)
- `content`: Full blog content (required)
- `excerpt`: Short excerpt for previews
- `status`: Published status (draft, published, archived)
- `category_id`: Category ID (optional)
- `published_at`: Publication date (auto-set if publishing)
- `tags`: Array of tag objects (can be existing IDs or new tag names)
- `seo_meta`: SEO metadata object (optional)

### Update Blog
```http
PUT /api/v1/blogs/{id}
```

Same request body format as create. Only provided fields will be updated.

### Delete Blog
```http
DELETE /api/v1/blogs/{id}
```

Deletes the blog and all related data (tags associations, SEO meta).

### Bulk Operations
```http
POST /api/v1/blogs/bulk
```

**Request Body:**
```json
{
  "action": "publish",
  "ids": [1, 2, 3, 4, 5]
}
```

**Available Actions:**
- `delete`: Delete selected blogs
- `publish`: Publish selected blogs
- `draft`: Move to draft status
- `archive`: Archive selected blogs

## Category Management

### List Categories
```http
GET /api/v1/categories?hierarchical=true
```

**Query Parameters:**
- `hierarchical` (optional): Return hierarchical structure (default: false)

### Get Category Hierarchy
```http
GET /api/v1/categories/hierarchy
```

Returns categories in a nested hierarchical structure.

### Create Category
```http
POST /api/v1/categories
```

**Request Body:**
```json
{
  "name": "Technology",
  "slug": "technology",
  "description": "Technology related posts",
  "parent_id": null,
  "seo_meta": {
    "meta_title": "Technology Category",
    "meta_description": "All technology related posts"
  }
}
```

### Update Category
```http
PUT /api/v1/categories/{id}
```

### Delete Category
```http
DELETE /api/v1/categories/{id}
```

Note: Cannot delete categories with existing blogs or child categories.

## Tag Management

### List Tags
```http
GET /api/v1/tags?with_count=true
```

**Query Parameters:**
- `with_count` (optional): Include blog count for each tag

### Search Tags
```http
GET /api/v1/tags/search?q=search_term&limit=10
```

### Create Tag
```http
POST /api/v1/tags
```

**Request Body:**
```json
{
  "name": "PHP",
  "slug": "php"
}
```

### Bulk Delete Tags
```http
DELETE /api/v1/tags/bulk
```

**Request Body:**
```json
{
  "ids": [1, 2, 3]
}
```

## Media Management

### Upload Single File
```http
POST /api/v1/media/upload
```

**Request:** Multipart form data with `file` field

**Supported File Types:**
- Images: JPEG, PNG, GIF, WebP
- Documents: PDF, TXT, DOC, DOCX

**Maximum Size:** 10MB

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "filename": "unique_filename.jpg",
    "original_name": "original.jpg",
    "mime_type": "image/jpeg",
    "size": 150000,
    "path": "uploads/images/unique_filename.jpg",
    "url": "/uploads/images/unique_filename.jpg",
    "width": 1920,
    "height": 1080,
    "uploaded_at": "2024-01-01 12:00:00"
  }
}
```

### Upload Multiple Files
```http
POST /api/v1/media/upload/multiple
```

**Request:** Multipart form data with `files[]` field (array of files)

### List Files
```http
GET /api/v1/media/list?type=images&page=1&limit=20
```

**Query Parameters:**
- `type` (optional): File type filter (all, images, documents, default: all)
- `page` (optional): Page number
- `limit` (optional): Items per page (5-50, default: 20)

### Delete File
```http
DELETE /api/v1/media/{filename}?subdir=images
```

**Query Parameters:**
- `subdir` (optional): Subdirectory (images, documents, default: images)

## Error Handling

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message (in debug mode)"
}
```

**Common HTTP Status Codes:**
- `200`: Success
- `201`: Created
- `400`: Bad Request (validation errors)
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `500`: Internal Server Error

## Usage Examples

### Creating a Complete Blog Post

1. **Upload featured image:**
```javascript
const formData = new FormData();
formData.append('file', imageFile);

const imageResponse = await fetch('/api/v1/media/upload', {
  method: 'POST',
  body: formData
});
const imageData = await imageResponse.json();
```

2. **Create the blog post:**
```javascript
const blogData = {
  title: "My New Blog Post",
  content: "Full content here...",
  excerpt: "Short excerpt...",
  status: "published",
  category_id: 1,
  tags: [
    {name: "PHP"},
    {name: "Web Development"}
  ],
  seo_meta: {
    meta_title: "My New Blog Post - My Site",
    meta_description: "Learn about...",
    og_image: imageData.data.url
  }
};

const response = await fetch('/api/v1/blogs', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(blogData)
});
```

### Bulk Publishing Drafts

```javascript
const response = await fetch('/api/v1/blogs/bulk', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    action: 'publish',
    ids: [1, 2, 3, 4, 5]
  })
});
```

## Security Notes

- All API endpoints require admin authentication
- File uploads are validated for type and size
- SQL injection protection through prepared statements
- XSS protection through output encoding
- CSRF protection through security headers middleware

This API provides complete WordPress-like functionality for managing your blog content through a React frontend.
