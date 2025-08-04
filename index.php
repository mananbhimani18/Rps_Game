<?php
session_start(); 

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rps_game";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$options = ["rock", "paper", "scissors"];
$score = [
    'wins' => 0,
    'losses' => 0,
    'ties' => 0
];
$background_color = 'white'; 

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['play'])) {
    $player_name = $_SESSION["username"]; 
    $player_choice = $_POST["choice"];
    $computer_choice = $options[array_rand($options)];
    
    $sql = "SELECT * FROM game_results WHERE player_name = '$player_name'";
    $result = $conn->query($sql);

    if ($result->num_rows == 0) {
        echo "<script>alert('Welcome $player_name to the game!');</script>";
        $conn->query("INSERT INTO game_results (player_name, result) VALUES ('$player_name', 'none')");
    }

    if ($player_choice == $computer_choice) {
        $result = 'draw';
        $background_color = '#ffff7a'; // Tie
    } elseif (
        ($player_choice == 'rock' && $computer_choice == 'scissors') ||
        ($player_choice == 'paper' && $computer_choice == 'rock') ||
        ($player_choice == 'scissors' && $computer_choice == 'paper')
    ) {
        $result = 'win';
        $background_color = '#90ff54'; // Win
    } else {
        $result = 'lose';
        $background_color = '#e03f3f'; // Lose
    }
    $player_img = "rps.png";
    echo "<img src='$player_img' alt='$player_choice' style='width: 36%; height: 34%;cursor:pointer' onclick='showScores()'>";

    $conn->query("INSERT INTO game_results (player_name, result) VALUES ('$player_name', '$result')");
    echo "<div style='text-align:center; font-size:35px; color: black; text-shadow: 2px 2px 5px rgb(255, 255, 255);'>You chose $player_choice, Computer chose $computer_choice. You $result!</div>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $player_name = $_SESSION["username"];
    $conn->query("DELETE FROM game_results WHERE player_name = '$player_name'");
    echo "<script>alert('Goodbye $player_name! Your records have been deleted.');</script>";
}

$player_name = $_SESSION["username"];
$sql = "SELECT result FROM game_results WHERE player_name = '$player_name'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['result'] == 'win') $score['wins']++;
        elseif ($row['result'] == 'lose') $score['losses']++;
        elseif ($row['result'] == 'draw') $score['ties']++;
    }
}

$conn->close();

// Calculate the win percentage
$total_games = $score['wins'] + $score['losses'] + $score['ties'];
$win_percentage = $total_games > 0 ? ($score['wins'] / $total_games) * 100 : 0;

// PDF download functionality
// PDF download functionality
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['download_pdf'])) {
    require('fpdf186/fpdf.php'); // Include the FPDF library

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);

    $pdf->Cell(200, 10, 'Rock Paper Scissors - Score Report', 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->Cell(200, 10, 'Player: ' . $player_name, 0, 1, 'L');
    $pdf->Cell(200, 10, 'Wins: ' . $score['wins'], 0, 1, 'L');
    $pdf->Cell(200, 10, 'Losses: ' . $score['losses'], 0, 1, 'L');
    $pdf->Cell(200, 10, 'Ties: ' . $score['ties'], 0, 1, 'L');

    // Set color for win percentage based on value
    if ($win_percentage < 50) {
        $pdf->SetTextColor(255, 0, 0); // Red for less than 50%
    } elseif ($win_percentage > 50) {
        $pdf->SetTextColor(0, 255, 0); // Green for more than 50%
    } else {
        $pdf->SetTextColor(0, 0, 0); // Black for exactly 50%
    }
    
    $pdf->Cell(200, 10, 'Win Percentage: ' . number_format($win_percentage, 2) . '%', 0, 1, 'L');

    // Output the PDF to the browser and prompt for download
    $pdf->Output('D', $player_name . '_result.pdf');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rock Paper Scissors Game</title>
    <style>
        body {
            text-align: center;
            background-color: <?php echo htmlspecialchars($background_color); ?>;
        }
        h3,p{
            text-shadow: 2px 2px 5px rgb(255, 255, 255);
        }

        .btn {
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 1) 0%,   
                rgba(0, 0, 0, 0) 56%,       
                rgba(255, 255, 255, 1) 100%
            );
            border: 2px solid black;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
            cursor: pointer;
            border-radius: 8px;
            transition: background 0.3s ease, transform 0.2s ease;
            box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
        }

        .btn:hover {
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 1) 0%,   
                rgba(0, 0, 0, 0) 70%,       
                rgba(255, 255, 255, 1) 100%
            );
            transform: scale(1.05);
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        label {
            font-size: 25px;    
            text-shadow: 2px 2px 5px rgb(255, 255, 255);
        }
    </style>
    <script>
        function showScores() {
            var wins = <?php echo $score['wins']; ?>;
            var losses = <?php echo $score['losses']; ?>;
            var ties = <?php echo $score['ties']; ?>;

            alert("Current Scores:\nWins: " + wins + "\nLosses: " + losses + "\nTies: " + ties);
        }
    </script>
</head>

<body>
    <h2 style="text-shadow: 2px 2px 5px rgb(255, 255, 255);">Welcome <?php echo $_SESSION["username"]; ?>!</h2>

    <form method="POST">
        <label>Choose Rock, Paper, or Scissors:</label><br>
        <input type="radio" name="choice" value="rock" required style="margin-top:25px" id="rock"> 
        <label for="rock">Rock</label>
        <input type="radio" name="choice" value="paper" required id="paper">
        <label for="paper">Paper</label> 
        <input type="radio" name="choice" value="scissors" required id="scissors"> 
        <label for="scissors">Scissors</label>
        <br><br>
        <input type="submit" class="btn" name="delete" value="Delete Records">
        <input type="submit" class="btn" name="play" value="Play">
    </form>

    <h3>Your Score:</h3>
    <p>Wins: <?php echo $score['wins']; ?> | Losses: <?php echo $score['losses']; ?> | Ties: <?php echo $score['ties']; ?></p>

    <form method="POST">
        <input type="submit" name="logout" value="Logout" class="btn" style="border:2px solid black; border-radius:50%; font-size: 15;padding: 10;">
        <input type="submit" name="download_pdf" value="Download Score as PDF" class="btn">
    </form>
</body>
</html>
