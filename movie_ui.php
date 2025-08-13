<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuru Movie Explorer</title>
    <style>
        :root {
            --background-color: #141414;
            --card-background: #1c1c1c;
            --text-color: #ffffff;
            --text-muted: #a0a0a0;
            --primary-color: #e50914;
            --border-color: #303030;
        }
        body { background-color: var(--background-color); color: var(--text-color); font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid var(--border-color); }
        header h1 { color: var(--primary-color); margin: 0; }
        .search-bar { width: 300px; }
        .search-bar input { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid var(--border-color); background-color: var(--card-background); color: var(--text-color); }
        .content-section h2 { margin-top: 40px; margin-bottom: 20px; font-size: 1.8em; }
        .movie-row { display: flex; overflow-x: auto; gap: 20px; padding-bottom: 20px; scrollbar-width: thin; scrollbar-color: var(--primary-color) var(--card-background); }
        .movie-row::-webkit-scrollbar { height: 8px; }
        .movie-row::-webkit-scrollbar-track { background: var(--card-background); }
        .movie-row::-webkit-scrollbar-thumb { background-color: var(--primary-color); border-radius: 10px; }
        .movie-card { background-color: var(--card-background); border-radius: 10px; overflow: hidden; cursor: pointer; transition: transform 0.2s; flex-shrink: 0; width: 200px; }
        .movie-card:hover { transform: scale(1.05); }
        .movie-card img { width: 100%; height: 300px; object-fit: cover; }
        .movie-card-info { padding: 15px; }
        .movie-card-info h3 { margin: 0; font-size: 1.1em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .movie-card-info p { margin: 5px 0 0; color: var(--text-muted); }
        .detail-view { display: none; padding-top: 40px; }
        .detail-view.active { display: block; }
        .detail-header { display: flex; flex-direction: column; md:flex-direction: row; gap: 40px; }
        .detail-header img { width: 100%; max-width: 300px; border-radius: 10px; margin: 0 auto; }
        .detail-info h2 { margin-top: 20px; md:margin-top: 0; font-size: 2.5em; }
        .detail-info p { line-height: 1.6; font-size: 1.1em; }
        .watch-button, .back-button { display: inline-block; margin-top: 20px; padding: 15px 30px; color: var(--text-color); text-decoration: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .watch-button { background-color: var(--primary-color); }
        .back-button { background-color: var(--border-color); margin-left: 10px; }
        .loader { text-align: center; font-size: 1.5em; padding: 50px; }
        /* Search results grid */
        .search-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        @media (min-width: 768px) { .detail-header { flex-direction: row; } .detail-header img { margin: 0; } }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Kuru Movie Explorer</h1>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search for movies or TV shows...">
            </div>
        </header>

        <main id="mainContent"></main>
        <div id="detailView" class="detail-view"></div>
    </div>

    <script>
        const API_BASE = 'movie/index.php';
        const IMG_BASE = 'https://image.tmdb.org/t/p/w500';
        
        const mainContent = document.getElementById('mainContent');
        const detailView = document.getElementById('detailView');
        const searchInput = document.getElementById('searchInput');

        async function fetchData(route, params = {}) {
            const url = new URL(API_BASE, window.location.origin);
            url.searchParams.append('route', route);
            for (const key in params) { url.searchParams.append(key, params[key]); }
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error("Error fetching data: ", error);
                return null;
            }
        }

        function createMovieCard(item, type) {
            const card = document.createElement('div');
            card.className = 'movie-card';
            card.innerHTML = `
                <img src="${item.image ? IMG_BASE + item.image : 'https://via.placeholder.com/200x300'}" alt="${item.orig_title}">
                <div class="movie-card-info">
                    <h3>${item.orig_title}</h3>
                    <p>${item.year || 'N/A'}</p>
                </div>`;
            card.addEventListener('click', () => showDetailView(item.id, type));
            return card;
        }

        function renderBrowsePage(lists) {
            mainContent.innerHTML = `
                <div class="content-section"><h2>Now Playing</h2><div class="movie-row" id="nowPlayingGrid"></div></div>
                <div class="content-section"><h2>Popular</h2><div class="movie-row" id="popularGrid"></div></div>
                <div class="content-section"><h2>Top Rated</h2><div class="movie-row" id="topRatedGrid"></div></div>
                <div class="content-section"><h2>Upcoming</h2><div class="movie-row" id="upcomingGrid"></div></div>`;
            
            populateRow('#nowPlayingGrid', lists.nowPlaying);
            populateRow('#popularGrid', lists.popular);
            populateRow('#topRatedGrid', lists.topRated);
            populateRow('#upcomingGrid', lists.upcoming);
        }

        function populateRow(selector, response) {
            const container = document.querySelector(selector);
            if (response && response.data && response.data.length > 0) {
                container.innerHTML = '';
                response.data.forEach(movie => container.appendChild(createMovieCard(movie, 'movie')));
            } else {
                container.innerHTML = '<p>Could not load this section.</p>';
            }
        }

        async function handleSearch(query) {
            if (query.length < 2) { loadInitialData(); return; }
            mainContent.innerHTML = '<div class="loader">Searching...</div>';
            const searchResults = await fetchData('search', { query });
            mainContent.innerHTML = `
                <div class="content-section">
                    <h2>Search Results for "${query}"</h2>
                    <div class="search-grid" id="searchResultsGrid"></div>
                </div>`;
            const resultsGrid = document.getElementById('searchResultsGrid');
            if (searchResults && searchResults.data && searchResults.data.length > 0) {
                searchResults.data.forEach(item => resultsGrid.appendChild(createMovieCard(item, item.type)));
            } else {
                resultsGrid.innerHTML = '<p>No results found.</p>';
            }
        }

        async function showDetailView(id, type) {
            mainContent.style.display = 'none';
            detailView.innerHTML = '<div class="loader">Loading...</div>';
            detailView.classList.add('active');
            const route = type === 'movie' ? 'movie' : 'watch-tv';
            const details = await fetchData(route, { id });
            if (details) renderDetailView(details);
            else detailView.innerHTML = '<div class="loader">Failed to load details.</div>';
        }

        function renderDetailView(details) {
            detailView.innerHTML = `<div class="detail-header"><img src="${details.poster_path ? IMG_BASE + details.poster_path : 'https://via.placeholder.com/300x450'}" alt="${details.title}"><div class="detail-info"><h2>${details.title}</h2><p>${details.overview}</p><p><strong>Release Date:</strong> ${details.release_date}</p><a href="${details.cleanPageUrl}" target="_blank" class="watch-button">Watch Now</a><button class="back-button">Back</button></div></div>`;
            detailView.querySelector('.back-button').addEventListener('click', () => {
                detailView.classList.remove('active');
                mainContent.style.display = 'block';
                if (searchInput.value.length > 1) handleSearch(searchInput.value);
                else loadInitialData();
            });
        }

        async function loadInitialData() {
            mainContent.style.display = 'block';
            detailView.classList.remove('active');
            mainContent.innerHTML = '<div class="loader">Loading Kuru Movie Explorer...</div>';
            const [nowPlaying, popular, topRated, upcoming] = await Promise.all([
                fetchData('movies', { list_type: 'now_playing' }),
                fetchData('movies', { list_type: 'popular' }),
                fetchData('movies', { list_type: 'top_rated' }),
                fetchData('movies', { list_type: 'upcoming' })
            ]);
            renderBrowsePage({ nowPlaying, popular, topRated, upcoming });
        }

        let searchTimeout;
        searchInput.addEventListener('input', e => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => handleSearch(e.target.value), 500);
        });

        document.addEventListener('DOMContentLoaded', loadInitialData);
    </script>
</body>
</html>