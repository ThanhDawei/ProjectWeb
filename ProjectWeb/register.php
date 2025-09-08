<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet"href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Register</title>
</head>
<body>
    <button class="logo-btn" type="button" aria-label="Go home"
            onclick="location.href='index.php'">
        <img src="images/BlueMoonLogo.png" alt="BlueMoon Logo">
    </button>
    <div class="logo-line"></div>
    <div class="form-wrapper">
        <div class="container">
            <form id="form_reg" class="form-card" action="reg.php" method="post">
                <h2 class="py-3 text-center text-uppercase">Register</h2>

                <div class="form-group">
                    <label for="fullname">Name</label>
                    <input type="text" name="fullname" class="form-control" id="fullname">
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" class="form-control" id="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" class="form-control" id="password">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" class="form-control" id="email">
                </div>

                <div class="form-group">
                    <label>Sex</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gender" id="male" value="male" checked>
                            <label class="form-check-label" for="male">Male</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gender" id="female" value="female">
                            <label class="form-check-label" for="female">Female</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" name="address" class="form-control" id="address">
                </div>

                <input type="submit" class="btn btn-primary btn-block mt-4" name="btn-reg" value="Register">
            </form>
        </div>
    </div>
</body>
</html>
