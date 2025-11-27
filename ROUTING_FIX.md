# Blog Routing Fix - Double /blog/ Prefix Issue

## Problem Description

In production on `https://boganto.com`, blog post URLs were incorrectly showing:
- ❌ `https://boganto.com/blog/blog/jujutsu-kaisen` (double /blog/)
- ✅ Should be: `https://boganto.com/blog/jujutsu-kaisen`

On localhost (`http://localhost:5173`), everything worked correctly.

## Root Cause

The blog API backend was returning slugs that sometimes included the `blog/` prefix. When the frontend code prepended `/blog/` to these slugs, it resulted in double prefixes like `/blog/blog/slug`.

## Solution Implemented

### 1. **Added Slug Cleaning Utility** (`utils/api.js`)
```javascript
cleanSlug: (slug) => {
  if (!slug) return slug;
  return slug.replace(/^\/blog\//, '').replace(/^blog\//, '');
}
```

This utility removes any `/blog/` or `blog/` prefix from slugs before using them.

### 2. **Updated Components**

#### BlogCard Component (`components/BlogCard.jsx`)
- Cleans slug before rendering links
- All `<Link>` components now use `cleanSlug`

#### Sidebar Component (`components/Sidebar.jsx`)
- Cleans slug in the SidebarBlogItem component
- Ensures sidebar blog links work correctly

#### Index Page (`pages/index.js`)
- All blog links clean slugs inline with `.replace()` calls
- Prevents double /blog/ prefix in featured and latest blog sections

#### Blog Detail Page (`pages/blog/[slug].js`)
- Cleans slug in `getServerSideProps` (server-side)
- Cleans slug in `fetchBlogDetail` (client-side)
- Cleans slug in related articles navigation

### 3. **How It Works**

```
Backend API returns slug: "blog/one-piece" or "one-piece"
                                    ↓
            cleanSlug utility: "one-piece"
                                    ↓
    Frontend code adds: "/blog/one-piece"
                                    ↓
        Final URL: "https://boganto.com/blog/one-piece" ✅
```

## Files Modified

1. `utils/api.js` - Added `cleanSlug` utility function
2. `components/BlogCard.jsx` - Clean slug before Link hrefs
3. `components/Sidebar.jsx` - Clean slug in sidebar items
4. `pages/index.js` - Inline slug cleaning in all blog links
5. `pages/blog/[slug].js` - Clean slug in SSR and client-side fetching

## Testing Instructions

### Localhost Testing
```bash
npm run dev
# Visit http://localhost:5173
# Click on any blog post - should navigate to /blog/slug (not /blog/blog/slug)
```

### Production Testing
```bash
npm run build
npm start
# Visit http://localhost:3000
# Click on any blog post - verify URLs are clean
```

### Production Deployment
After deploying to `https://boganto.com`:
1. Visit `https://boganto.com/blog`
2. Click on any blog post
3. Verify URL is `https://boganto.com/blog/[slug]` (NOT `/blog/blog/[slug]`)
4. Test related article links within blog posts
5. Test sidebar blog links
6. Test featured and latest blog links from homepage

## Expected Behavior

| Location | URL Pattern | Example |
|----------|-------------|---------|
| Blog Home | `/blog` | `https://boganto.com/blog` |
| Blog Post | `/blog/[slug]` | `https://boganto.com/blog/one-piece` |
| Category | `/category/[slug]` | `https://boganto.com/category/manga` |
| Tag | `/tag/[tag]` | `https://boganto.com/tag/anime` |

## Additional Notes

- The fix is **defensive** - it cleans slugs even if the backend is fixed
- Works in both **localhost** (dev) and **production** environments
- **No changes needed** to backend API
- **Backward compatible** - works with both clean slugs and prefixed slugs from API

## Rollback Instructions

If needed, revert the commit:
```bash
git revert 5bf92a7
git push origin main
```

## Related Issues

- Localhost worked fine because URLs were `/blog/slug`
- Production had issues because reverse proxy or backend added extra `/blog/` prefix
- Solution prevents this at the frontend level regardless of backend behavior
