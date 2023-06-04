<?php
// MySQL configuration
$servername = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "hiscores";

// Function to retrieve the old level from the database for a given skill
function getOldLevel($conn, $username, $skill)
{
    // Determine the correct column name for the given skill
    $column = strtolower($skill) . "_level";

    $selectPlayerSQL = "SELECT $column FROM profiles WHERE username = '$username'";
    $selectPlayerResult = $conn->query($selectPlayerSQL);

    if ($selectPlayerResult && $selectPlayerResult->num_rows > 0) {
        $row = $selectPlayerResult->fetch_assoc();
        $oldLevel = (int) $row[$column];
    } else {
        $oldLevel = 0;
    }

    return $oldLevel;
}

// Function to retrieve the old experience from the database for a given skill
function getOldXP($conn, $username, $skill)
{
    // Determine the correct column name for the given skill
    $column = strtolower($skill) . "_experience";

    $selectPlayerSQL = "SELECT $column FROM profiles WHERE username = '$username'";
    $selectPlayerResult = $conn->query($selectPlayerSQL);

    if ($selectPlayerResult && $selectPlayerResult->num_rows > 0) {
        $row = $selectPlayerResult->fetch_assoc();
        $oldXP = (int) $row[$column];
    } else {
        $oldXP = 0;
    }

    return $oldXP;
}

if (isset($_GET['username'])) {
    // Retrieve the username from the query string
    $username = $_GET['username'];

    // Create a new MySQL connection
    $conn = new mysqli($servername, $db_username, $db_password, $db_name);

    // Check the MySQL connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

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

                // Get the old level and experience for the skill
                $oldLevel = getOldLevel($conn, $username, $skillNames[$i]);
                $oldXP = getOldXP($conn, $username, $skillNames[$i]);

                // Add the skill progress to the player's data
                $player['progress'][] = [
                    'skill' => $skillNames[$i],
                    'rank' => $rank,
                    'current_level' => $level,
                    'current_experience' => $experience,
                    'old_level' => $oldLevel,
                    'old_experience' => $oldXP,
                    'progress' => $progress,
                    'progress_color' => $progressColor,
                ];
            }
        }

        // Get the current views count from the database
        $views = 0;

        $selectViewsSQL = "SELECT views_count FROM profiles WHERE username = '$username'";
        $selectViewsResult = $conn->query($selectViewsSQL);
        if ($selectViewsResult && $selectViewsResult->num_rows > 0) {
            $row = $selectViewsResult->fetch_assoc();
            $views = (int) $row['views_count'];
        }

        // Increase the views count by 1
        $views++;

        // Check if the player already exists in the database
        $selectPlayerSQL = "SELECT * FROM profiles WHERE username = '$username'";
        $selectPlayerResult = $conn->query($selectPlayerSQL);

        if ($selectPlayerResult && $selectPlayerResult->num_rows > 0) {
            // Update the player's level, experience, and views count in the database
            $updatePlayerSQL = "UPDATE profiles SET 
                overall_level = " . $player['total_level'] . ",
                overall_experience = " . $player['total_experience'] . ",
                attack_level = " . $player['progress'][1]['current_level'] . ",
                attack_experience = " . $player['progress'][1]['current_experience'] . ",
                defence_level = " . $player['progress'][2]['current_level'] . ",
                defence_experience = " . $player['progress'][2]['current_experience'] . ",
                strength_level = " . $player['progress'][3]['current_level'] . ",
                strength_experience = " . $player['progress'][3]['current_experience'] . ",
                constitution_level = " . $player['progress'][4]['current_level'] . ",
                constitution_experience = " . $player['progress'][4]['current_experience'] . ",
                ranged_level = " . $player['progress'][5]['current_level'] . ",
                ranged_experience = " . $player['progress'][5]['current_experience'] . ",
                prayer_level = " . $player['progress'][6]['current_level'] . ",
                prayer_experience = " . $player['progress'][6]['current_experience'] . ",
                magic_level = " . $player['progress'][7]['current_level'] . ",
                magic_experience = " . $player['progress'][7]['current_experience'] . ",
                cooking_level = " . $player['progress'][8]['current_level'] . ",
                cooking_experience = " . $player['progress'][8]['current_experience'] . ",
                woodcutting_level = " . $player['progress'][9]['current_level'] . ",
                woodcutting_experience = " . $player['progress'][9]['current_experience'] . ",
                fletching_level = " . $player['progress'][10]['current_level'] . ",
                fletching_experience = " . $player['progress'][10]['current_experience'] . ",
                fishing_level = " . $player['progress'][11]['current_level'] . ",
                fishing_experience = " . $player['progress'][11]['current_experience'] . ",
                firemaking_level = " . $player['progress'][12]['current_level'] . ",
                firemaking_experience = " . $player['progress'][12]['current_experience'] . ",
                crafting_level = " . $player['progress'][13]['current_level'] . ",
                crafting_experience = " . $player['progress'][13]['current_experience'] . ",
                smithing_level = " . $player['progress'][14]['current_level'] . ",
                smithing_experience = " . $player['progress'][14]['current_experience'] . ",
                mining_level = " . $player['progress'][15]['current_level'] . ",
                mining_experience = " . $player['progress'][15]['current_experience'] . ",
                herblore_level = " . $player['progress'][16]['current_level'] . ",
                herblore_experience = " . $player['progress'][16]['current_experience'] . ",
                agility_level = " . $player['progress'][17]['current_level'] . ",
                agility_experience = " . $player['progress'][17]['current_experience'] . ",
                thieving_level = " . $player['progress'][18]['current_level'] . ",
                thieving_experience = " . $player['progress'][18]['current_experience'] . ",
                slayer_level = " . $player['progress'][19]['current_level'] . ",
                slayer_experience = " . $player['progress'][19]['current_experience'] . ",
                farming_level = " . $player['progress'][20]['current_level'] . ",
                farming_experience = " . $player['progress'][20]['current_experience'] . ",
                runecrafting_level = " . $player['progress'][21]['current_level'] . ",
                runecrafting_experience = " . $player['progress'][21]['current_experience'] . ",
                hunter_level = " . $player['progress'][22]['current_level'] . ",
                hunter_experience = " . $player['progress'][22]['current_experience'] . ",
                construction_level = " . $player['progress'][23]['current_level'] . ",
                construction_experience = " . $player['progress'][23]['current_experience'] . ",
                summoning_level = " . $player['progress'][24]['current_level'] . ",
                summoning_experience = " . $player['progress'][24]['current_experience'] . ",
                dungeoneering_level = " . $player['progress'][25]['current_level'] . ",
                dungeoneering_experience = " . $player['progress'][25]['current_experience'] . ",
                divination_level = " . $player['progress'][26]['current_level'] . ",
                divination_experience = " . $player['progress'][26]['current_experience'] . ",
                invention_level = " . $player['progress'][27]['current_level'] . ",
                invention_experience = " . $player['progress'][27]['current_experience'] . ",
                archaeology_level = " . $player['progress'][28]['current_level'] . ",
                archaeology_experience = " . $player['progress'][28]['current_experience'] . ",
                views_count = " . $views . "
            WHERE username = '$username'";

            if ($conn->query($updatePlayerSQL) === false) {
                echo "Error updating record: " . $conn->error;
            }
        } else {
            // Insert the player's data into the database
            $insertPlayerSQL = "INSERT INTO profiles (
                username,
                overall_level,
                overall_experience,
                attack_level,
                attack_experience,
                defence_level,
                defence_experience,
                strength_level,
                strength_experience,
                constitution_level,
                constitution_experience,
                ranged_level,
                ranged_experience,
                prayer_level,
                prayer_experience,
                magic_level,
                magic_experience,
                cooking_level,
                cooking_experience,
                woodcutting_level,
                woodcutting_experience,
                fletching_level,
                fletching_experience,
                fishing_level,
                fishing_experience,
                firemaking_level,
                firemaking_experience,
                crafting_level,
                crafting_experience,
                smithing_level,
                smithing_experience,
                mining_level,
                mining_experience,
                herblore_level,
                herblore_experience,
                agility_level,
                agility_experience,
                thieving_level,
                thieving_experience,
                slayer_level,
                slayer_experience,
                farming_level,
                farming_experience,
                runecrafting_level,
                runecrafting_experience,
                hunter_level,
                hunter_experience,
                construction_level,
                construction_experience,
                summoning_level,
                summoning_experience,
                dungeoneering_level,
                dungeoneering_experience,
                divination_level,
                divination_experience,
                invention_level,
                invention_experience,
                archaeology_level,
                archaeology_experience,
                views_count
            ) VALUES (
                '$username',
                " . $player['total_level'] . ",
                " . $player['total_experience'] . ",
                " . $player['progress'][1]['current_level'] . ",
                " . $player['progress'][1]['current_experience'] . ",
                " . $player['progress'][2]['current_level'] . ",
                " . $player['progress'][2]['current_experience'] . ",
                " . $player['progress'][3]['current_level'] . ",
                " . $player['progress'][3]['current_experience'] . ",
                " . $player['progress'][4]['current_level'] . ",
                " . $player['progress'][4]['current_experience'] . ",
                " . $player['progress'][5]['current_level'] . ",
                " . $player['progress'][5]['current_experience'] . ",
                " . $player['progress'][6]['current_level'] . ",
                " . $player['progress'][6]['current_experience'] . ",
                " . $player['progress'][7]['current_level'] . ",
                " . $player['progress'][7]['current_experience'] . ",
                " . $player['progress'][8]['current_level'] . ",
                " . $player['progress'][8]['current_experience'] . ",
                " . $player['progress'][9]['current_level'] . ",
                " . $player['progress'][9]['current_experience'] . ",
                " . $player['progress'][10]['current_level'] . ",
                " . $player['progress'][10]['current_experience'] . ",
                " . $player['progress'][11]['current_level'] . ",
                " . $player['progress'][11]['current_experience'] . ",
                " . $player['progress'][12]['current_level'] . ",
                " . $player['progress'][12]['current_experience'] . ",
                " . $player['progress'][13]['current_level'] . ",
                " . $player['progress'][13]['current_experience'] . ",
                " . $player['progress'][14]['current_level'] . ",
                " . $player['progress'][14]['current_experience'] . ",
                " . $player['progress'][15]['current_level'] . ",
                " . $player['progress'][15]['current_experience'] . ",
                " . $player['progress'][16]['current_level'] . ",
                " . $player['progress'][16]['current_experience'] . ",
                " . $player['progress'][17]['current_level'] . ",
                " . $player['progress'][17]['current_experience'] . ",
                " . $player['progress'][18]['current_level'] . ",
                " . $player['progress'][18]['current_experience'] . ",
                " . $player['progress'][19]['current_level'] . ",
                " . $player['progress'][19]['current_experience'] . ",
                " . $player['progress'][20]['current_level'] . ",
                " . $player['progress'][20]['current_experience'] . ",
                " . $player['progress'][21]['current_level'] . ",
                " . $player['progress'][21]['current_experience'] . ",
                " . $player['progress'][22]['current_level'] . ",
                " . $player['progress'][22]['current_experience'] . ",
                " . $player['progress'][23]['current_level'] . ",
                " . $player['progress'][23]['current_experience'] . ",
                " . $player['progress'][24]['current_level'] . ",
                " . $player['progress'][24]['current_experience'] . ",
                " . $player['progress'][25]['current_level'] . ",
                " . $player['progress'][25]['current_experience'] . ",
                " . $player['progress'][26]['current_level'] . ",
                " . $player['progress'][26]['current_experience'] . ",
                " . $player['progress'][27]['current_level'] . ",
                " . $player['progress'][27]['current_experience'] . ",
                " . $player['progress'][28]['current_level'] . ",
                " . $player['progress'][28]['current_experience'] . ",
                " . $views . "
            )";

            if ($conn->query($insertPlayerSQL) === false) {
                echo "Error inserting record: " . $conn->error;
            }
        }

        // Close the MySQL connection
        $conn->close();
    }
} else {
    echo "No username provided.";
}
?>
