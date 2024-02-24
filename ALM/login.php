<?php
# Initialize session
session_start();

# Check if user is already logged in, If yes then redirect him to index page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] == TRUE) {
  echo "<script>" . "window.location.href='./'" . "</script>";
  exit;
}

# Include connection
require_once "./config.php";

# Define variables and initialize with empty values
$user_login_err = $user_password_err = $login_err = "";
$user_login = $user_password = "";

# Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (empty(trim($_POST["user_login"]))) {
    $user_login_err = "Please enter your username or an email id.";
  } else {
    $user_login = trim($_POST["user_login"]);
  }

  if (empty(trim($_POST["user_password"]))) {
    $user_password_err = "Please enter your password.";
  } else {
    $user_password = trim($_POST["user_password"]);
  }

  # Validate credentials 
  if (empty($user_login_err) && empty($user_password_err)) {
    # Prepare a select statement
    $sql = "SELECT id, username, password FROM users WHERE username = ? OR email = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
      # Bind variables to the statement as parameters
      mysqli_stmt_bind_param($stmt, "ss", $param_user_login, $param_user_login);

      # Set parameters
      $param_user_login = $user_login;

      # Execute the statement
      if (mysqli_stmt_execute($stmt)) {
        # Store result
        mysqli_stmt_store_result($stmt);

        # Check if user exists, If yes then verify password
        if (mysqli_stmt_num_rows($stmt) == 1) {
          # Bind values in result to variables
          mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);

          if (mysqli_stmt_fetch($stmt)) {
            # Check if password is correct
            if (password_verify($user_password, $hashed_password)) {

              # Store data in session variables
              $_SESSION["id"] = $id;
              $_SESSION["username"] = $username;
              $_SESSION["loggedin"] = TRUE;

              # Redirect user to index page
              echo "<script>" . "window.location.href='./'" . "</script>";
              exit;
            } else {
              # If password is incorrect show an error message
              $login_err = "The email or password you entered is incorrect.";
            }
          }
        } else {
          # If user doesn't exists show an error message
          $login_err = "Invalid username or password.";
        }
      } else {
        echo "<script>" . "alert('Oops! Something went wrong. Please try again later.');" . "</script>";
        echo "<script>" . "window.location.href='./login.php'" . "</script>";
        exit;
      }

      # Close statement
      mysqli_stmt_close($stmt);
    }
  }

  # Close connection
  mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <link rel="stylesheet" href="./css/main.css">
    <script defer src="./js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr"></script>
</head>
<body>
    <div class="container">
        <div class="row min-vh-100 justify-content-center align-items-center">
            <div class="col-lg-5">
                <?php
                if (!empty($login_err)) {
                    echo "<div class='alert alert-danger'>" . $login_err . "</div>";
                }
                ?>
                <div class="form-wrap border rounded p-4">
                    <h1>Log In</h1>
                    <p>Please login to continue</p>
                    <!-- form starts here -->
                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                        <div class="mb-3">
                            <label for="user_login" class="form-label">Email or username</label>
                            <input type="text" class="form-control" name="user_login" id="user_login" value="<?= $user_login; ?>">
                            <small class="text-danger"><?= $user_login_err; ?></small>
                        </div>
                        <div class="mb-2">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" name="user_password" id="password">
                            <small class="text-danger"><?= $user_password_err; ?></small>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="togglePassword">
                            <label for="togglePassword" class="form-check-label">Show Password</label>
                        </div>
                        <div class="mb-3">
                            <button type="button" id="startCameraButton" class="btn btn-primary form-control">Start Camera</button>
                        </div>
                        <div id="cameraPrompt" class="mb-3" style="display: none;">
                            <label for="cameraFeed" class="form-label">Camera Feed</label>
                        </div>
                        <div id="cameraFeed" style="display: none;">
                            <video id="videoElement" autoplay playsinline muted class="w-100"></video>
                        </div>
                        <div class="mb-3">
                            <input type="submit" class="btn btn-primary form-control" name="submit" value="Log In">
                        </div>
                        <!-- Add this element to display QR code detection message -->
                        <div id="qrCodeDetectedMessage" style="display: none;"></div>
                    </form>
                    <!-- form ends here -->
                </div>
            </div>
        </div>
    </div>

    <div id="background-wrap">
        <div class="bubble x1"></div>
        <div class="bubble x2"></div>
        <div class="bubble x3"></div>
        <div class="bubble x4"></div>
        <div class="bubble x5"></div>
        <div class="bubble x6"></div>
        <div class="bubble x7"></div>
        <div class="bubble x8"></div>
        <div class="bubble x9"></div>
        <div class="bubble x10"></div>
    </div>

    <style>
        body {
            font-family: "Barlow Semi Condensed", sans-serif;
            color: #333;
            font-size: 16px;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background-image: url('bg.gif'); /* Adjust the file name and path */
            background-size: cover;
            background-position: center;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const startCameraButton = document.getElementById('startCameraButton');
            const cameraPrompt = document.getElementById('cameraPrompt');
            const cameraFeed = document.getElementById('cameraFeed');
            const videoElement = document.getElementById('videoElement');
            const qrCodeDetectedMessage = document.getElementById('qrCodeDetectedMessage');

            startCameraButton.addEventListener('click', async () => {
                try {
                    // Request permission to access the camera
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true });

                    // Access granted, display the camera feed
                    console.log('Camera access granted');

                    cameraPrompt.style.display = 'block';
                    cameraFeed.style.display = 'block';
                    videoElement.srcObject = stream;

                    // Function to decode QR codes from video frames
                    function decodeQRCode() {
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        canvas.width = videoElement.videoWidth;
                        canvas.height = videoElement.videoHeight;
                        context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height);
                        if (code) {
                            console.log('QR code detected:', code.data);
                            qrCodeDetectedMessage.innerText = `QR Code detected: ${code.data}`;
                            // Optionally, you can perform additional actions here
                        } else {
                            qrCodeDetectedMessage.innerText = 'Scanning for QR codes...';
                        }
                        requestAnimationFrame(decodeQRCode);
                    }

                    // Start decoding QR codes
                    decodeQRCode();
                } catch (error) {
                    // Access denied or error occurred
                    console.error('Error accessing camera:', error);
                }
            });
        });
    </script>
</body>
</html>
