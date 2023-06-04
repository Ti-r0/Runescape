<?php
if (isset($_GET['username'])) {
    $username = $_GET['username'];

    // Include the hiscores.php file
    require_once 'hiscores.php';

    // Call the getOldLevel() and getOldXP() functions from the hiscores.php file
    $oldLevel = getOldLevel($username, $skillNames[$i]);
    $oldXP = getOldXP($username, $skillNames[$i]);
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuneScape Hiscores</title>
    <style>
        body {
            background: lightblue url("") no-repeat fixed center;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            text-align: center;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f1f1f1;
            position: sticky;
            top: 0;
            padding: 10px;
            width: 100%;
        }

        .navbar h1 {
            margin: 0;
        }

        .navbar ul {
            list-style: none;
            display: flex;
        }

        .navbar li {
            margin-right: 10px;
        }

        .navbar a {
            text-decoration: none;
            color: black;
        }

        .search-form {
            display: flex;
            align-items: center;
        }

        .search-input {
            margin-right: 10px;
        }

        .search-button {
            padding: 5px 10px;
        }

        .player-info {
            margin-bottom: 20px;
        }

        .chart {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }

        .skill {
            width: 150px;
            padding: 10px;
            margin: 10px;
            text-align: center;
            background-color: #f1f1f1;
            border-radius: 5px;
        }

        .uc-skills-list__skill-icon {
            display: inline-block;
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
            background-size: cover;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #ddd;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            transition: width 0.5s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .progress-label {
            padding: 0 5px;
            color: white;
        }

        .player-info-container {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: black;
        }

        .player-info-username {
            margin-top: 0;
        }

        .player-info-stats {
            display: flex;
            justify-content: space-between;
        }

        .player-info-stat {
            margin: 0;
        }

        .player-info-value {
            font-weight: bold;
        }

        .footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            background-color: #f1f1f1;
            padding: 10px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <h1>RS3 Hiscores</h1>
        <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">Discord</a></li>
        </ul>
        <?php
        if (!isset($_GET['username'])) {
            echo '
            <form class="search-form" method="GET">
                <input class="search-input" type="text" name="username" placeholder="Search your RuneScape Hiscores">
                <button class="search-button" type="submit">Search</button>
            </form>';
        } else {
            $username = $_GET['username'];
            echo '
            <form class="search-form" method="GET" action="index.php">
                <input class="search-input" type="text" name="username" placeholder="Enter username" value="' . $username . '">
                <button class="search-button" type="submit">Search</button>
            </form>';
        }
        ?>
    </div>

    <div class="container">
        <div class="player-info">
            <?php
            if (isset($_GET['username'])) {
                // Retrieve the username from the query string
                $username = $_GET['username'];

                // Construct the URL for the official RuneScape Hiscores API
                $url = "https://secure.runescape.com/m=hiscore/index_lite.ws?player=" . urlencode($username);

                // Fetch player data from the API
                $response = file_get_contents($url);

                if ($response) {
                    // Parse the player data
                    $playerData = explode("\n", $response);

                    // Define skill names, levels required for level 99, and skill icons
                    $skillNames = [
                        "Overall", "Attack", "Defence", "Strength", "Constitution", "Ranged",
                        "Prayer", "Magic", "Cooking", "Woodcutting", "Fletching", "Fishing",
                        "Firemaking", "Crafting", "Smithing", "Mining", "Herblore", "Agility",
                        "Thieving", "Slayer", "Farming", "Runecrafting", "Hunter", "Construction",
                        "Summoning", "Dungeoneering", "Divination", "Invention", "Archaeology"
                    ];
                    $skillLevels99 = array_fill(0, count($skillNames), 99);

                    // Create an associative array with the player data and skill progress
                    $player = [
                        'username' => $username,
                        'rank' => -1,
                        'total_level' => 0,
                        'total_experience' => 0,
                        'progress' => []
                    ];

                    // Extract the rank number, total level, and total experience from the player data
                    $playerInfo = explode(',', $playerData[0]);
                    if (count($playerInfo) >= 3) {
                        $player['rank'] = (int) $playerInfo[0];
                        $player['total_level'] = (int) $playerInfo[1];
                        $player['total_experience'] = (int) $playerInfo[2];
                    }

                    // Loop through each skill and calculate the progress towards level 99
                    for ($i = 0; $i < count($skillNames); $i++) {
                        $skillData = explode(",", $playerData[$i]);

                        if (count($skillData) >= 3) {
                            $rank = (int) $skillData[0];
                            $level = (int) $skillData[1];
                            $experience = (int) $skillData[2];

                            // Calculate the progress as a percentage
                            $progress = ($level / $skillLevels99[$i]) * 100;

                            if ($level === 99) {
                                // If skill is already at level 99, change progress bar color to gold
                                $progressColor = 'gold';
                            } else {
                                $progressColor = 'green';
                            }

                            // Get the old level and old experience from the hiscores.php file
                            $oldLevel = getOldLevel($username, $skillNames[$i]);
                            $oldXP = getOldXP($username, $skillNames[$i]);

                            // Add the skill progress to the player's data
                            $player['progress'][] = [
                                'skill' => $skillNames[$i],
                                'rank' => $rank,
                                'current_level' => $level,
                                'current_experience' => $experience,
                                'progress' => $progress,
                                'progress_color' => $progressColor,
                                'old_level' => $oldLevel,
                                'old_xp' => $oldXP,
                            ];
                        }
                    }

                    // Render the player's information and skills
                    echo '<div class="player-info-container">';
                    echo '<h2 class="player-info-username">' . $player['username'] . '</h2>';
                    echo '<div class="player-info-stats">';
                    echo '<p class="player-info-stat">Rank: <span class="player-info-value">' . number_format($player['rank']) . '</span></p>';
                    echo '<p class="player-info-stat">Total Level: <span class="player-info-value">' . number_format($player['total_level']) . '</span></p>';
                    echo '<p class="player-info-stat">Total Experience: <span class="player-info-value">' . number_format($player['total_experience']) . '</span></p>';
                    echo '</div>';
                    echo '</div>';
                } else {
                    // If player data is not found, return an error message
                    echo "<p>Player not found.</p>";
                }
            }
            
            /**
             * Retrieves the old level from hiscores.php for a given skill.
             * @param string $username The username of the player.
             * @param string $skill The name of the skill.
             * @return int|null The old level value, or null if not found.
             */
            function getOldLevel($username, $skill)
            {
                // Replace the database credentials with your own
                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "hiscores";

                // Create a new PDO instance
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

                // Prepare the SQL query
                $stmt = $conn->prepare("SELECT old_level FROM users WHERE username = :username AND skill = :skill");

                // Bind the username and skill parameters and execute the query
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':skill', $skill);
                $stmt->execute();

                // Fetch the result
                $result = $stmt->fetch();

                // Close the connection
                $conn = null;

                // Return the old level if found, otherwise return null
                return $result ? $result['old_level'] : null;
            }

            /**
             * Retrieves the old experience from the database for a given skill.
             * @param string $username The username of the player.
             * @param string $skill The name of the skill.
             * @return int|null The old experience value, or null if not found.
             */
            function getOldXP($username, $skill)
            {
                // Replace the database credentials with your own
                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "hiscores";

                // Create a new PDO instance
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

                // Prepare the SQL query
                $stmt = $conn->prepare("SELECT old_xp FROM users WHERE username = :username AND skill = :skill");

                // Bind the username and skill parameters and execute the query
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':skill', $skill);
                $stmt->execute();

                // Fetch the result
                $result = $stmt->fetch();

                // Close the connection
                $conn = null;

                // Return the old XP if found, otherwise return null
                return $result ? $result['old_xp'] : null;
            }
            ?>
        </div>

        <div class="chart">
            <?php
            if (isset($player['progress'])) {
                foreach ($player['progress'] as $skill) {
                    // Output skill information
                    echo '<div class="skill">';
                    // Output skill details including old level and old XP
                    echo '<p>' . $skill['skill'] . '</p>';
                    echo '<p>Rank: ' . number_format($skill['rank']) . '</p>';
                    echo '<p>Level: ' . number_format($skill['current_level']) . '</p>';
                    echo '<p>Experience: ' . number_format($skill['current_experience']) . '</p>';
                    echo '<p>Old Level: ' . ($skill['old_level'] !== null ? number_format($skill['old_level']) : 'N/A') . '</p>';
                    echo '<p>Old XP: ' . ($skill['old_xp'] !== null ? number_format($skill['old_xp']) : 'N/A') . '</p>';
                    // Output progress bar
                    echo '<div class="progress-bar">';
                    echo '<div class="progress-bar-fill" style="width: ' . $skill['progress'] . '%; background-color: ' . $skill['progress_color'] . ';">';
                    echo '<span class="progress-label">' . number_format($skill['progress'], 2) . '%</span>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>

        <footer class="footer">
            All Rights 2023
        </footer>
    </div>
</body>

</html>
