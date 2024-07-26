<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>@yield('title')</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css2?family=Nunito&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
            @import url("https://fonts.googleapis.com/css?family=Nunito:400,600,700");
			@keyframes floating {
			  from {
				transform: translateY(0px);
			  }
			  65% {
				transform: translateY(15px);
			  }
			  to {
				transform: translateY(0px);
			  }
			}
			html {
			  height: 100%;
			}

			body {
			  background-image: url("https://iotwavespaces.sgp1.digitaloceanspaces.com/public/star.svg"), linear-gradient(to bottom, #05007A, #4D007D);
			  height: 100%;
			  margin: 0;
			  background-attachment: fixed;
			  overflow: hidden;
			}

			.mars {
			  left: 0;
			  right: 0;
			  bottom: 0;
			  position: absolute;
			  height: 27vmin;
			  background: url("https://iotwavespaces.sgp1.digitaloceanspaces.com/public/mars.svg") no-repeat bottom center;
			  background-size: cover;
			}
			
			.logo-error {
			  position: absolute;
			  margin-left: auto;
			  margin-right: auto;
			  left: 0;
			  right: 0;
			  top: 16vmin;
			  width: 30vmin;
			}
			@media (max-width: 480px) and (min-width: 320px) {
			  .logo-error {
				top: 45vmin;
			  }
			}			

			.meteor {
			  position: absolute;
			  right: 2vmin;
			  top: 16vmin;
			}

			.title {
			  color: white;
			  font-family: "Nunito", sans-serif;
			  font-weight: 600;
			  text-align: center;
			  font-size: 5vmin;
			  margin-top: 31vmin;
			}
			@media (max-width: 480px) and (min-width: 320px) {
			  .title {
				margin-top: 65vmin;
			  }
			}
			
			.subtitle {
			  color: white;
			  font-family: "Nunito", sans-serif;
			  font-weight: 400;
			  text-align: center;
			  font-size: 3.5vmin;
			  margin-top: -1vmin;
			  margin-bottom: 9vmin;
			}

			.gap {
			  color: white;
			  font-family: "Nunito", sans-serif;
			  font-weight: 400;
			  text-align: center;
			  font-size: 2.5vmin;
			  margin-top: -1vmin;
			}
			
			.btn-back {
			  border: 1px solid white;
			  color: white;
			  height: 5vmin;
			  padding: 12px;
			  font-family: "Nunito", sans-serif;
			  text-decoration: none;
			  border-radius: 5px;
			}
			.btn-back:hover {
			  background: white;
			  color: #4D007D;
			}
			@media (max-width: 480px) and (min-width: 320px) {
			  .btn-back {
				font-size: 3.5vmin;
			  }
			}

			.astronaut {
			  position: absolute;
			  top: 18vmin;
			  left: 10vmin;
			  height: 30vmin;
			  animation: floating 3s infinite ease-in-out;
			}
			@media (max-width: 480px) and (min-width: 320px) {
			  .astronaut {
				top: 2vmin;
			  }
			}

			.spaceship {
			  position: absolute;
			  bottom: 15vmin;
			  right: 24vmin;
			}
			@media (max-width: 480px) and (min-width: 320px) {
			  .spaceship {
				width: 45vmin;
				bottom: 18vmin;
			  }
			}
        </style>
    </head>
    <body class="antialiased font-sans">
		<div class="mars"></div>
		@yield('image')
		<img src="https://iotwavespaces.sgp1.digitaloceanspaces.com/public/meteor.svg" class="meteor" />
		<p class="title">@yield('code', __('Oh no!!'))</p>
		<p class="subtitle">
			@yield('message')
		</p>
		<div align="center">
		  <a class="btn-back" href="{{ app('router')->has('home') ? route('home') : url('/') }}">{{ __('Go Home') }}</a>
		</div>
		<p class="gap">&nbsp;</p>		
		<div align="center">
		  <a class="btn-back" onclick="history.back()">Back to previous page</a>
		</div>		
		<img src="https://iotwavespaces.sgp1.digitaloceanspaces.com/public/astronaut.svg" class="astronaut" />
		<img src="https://iotwavespaces.sgp1.digitaloceanspaces.com/public/spaceship.svg" class="spaceship" />
    </body>
</html>
