# How to Get Your Directus API Key

Since your Directus is running in Docker, follow these steps:

## Method 1: From Directus Admin Panel (Easiest)

1. **Open Directus in Browser**
   - Go to: `http://localhost:8055`
   - Login with your Directus admin credentials

2. **Get API Key**
   - Click **Settings** (gear icon) in left sidebar
   - Go to **Access Control** → **Roles** → **Administrator**
   - Click **API Access** tab
   - Copy the **Static Token** (this is your API key)

   OR

   - Go to **Settings** → **Access Tokens**
   - Click **Create Token** or use existing token
   - Copy the token value

3. **Update config.php**
   - Open `admin-panel/config.php`
   - Replace `<PUT_MY_DIRECTUS_API_KEY_HERE>` with your API key
   - Save the file

## Method 2: From Docker Container

If you need to check Directus environment:

```bash
# Check Directus container
docker ps

# View Directus environment (if needed)
docker exec -it <directus-container-name> env | grep KEY
```

## Quick Test

After setting the API key, test the connection:

1. Visit: `http://localhost:8000/test_connection.php`
2. It will show if connection is working ✅

---

## Your Current Setup

- ✅ Docker Desktop running
- ✅ MySQL container running
- ✅ Directus container running on port 8055
- ⏳ Need to: Get API key and update config.php



