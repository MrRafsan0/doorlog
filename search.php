<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search — DoorLog</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>
    <div class="doorlog-wrapper">
        <div class="card" style="max-width:700px;">
            <div class="header-flex">
                <h2>Search Entries</h2>
            </div>
            <form id="searchForm" style="display:flex;gap:10px;margin-bottom:25px;">
                <input type="text" id="searchInput" placeholder="Search by address..."
                       style="margin:0;flex-grow:1;">
                <button type="submit" class="btn-blue"
                        style="width:auto;padding:10px 25px;margin:0;">Search</button>
            </form>
            <div id="searchResults" style="display:flex;flex-direction:column;gap:10px;"></div>
        </div>
    </div>
    <script src="script.js"></script>
    <script src="/global.js"></script>
</body>
</html>