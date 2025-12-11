<?php
/**
 * Created by A. MACHEDA
 * @author Genesii SAS
 * @version 1.0
 */
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<header class="site-header">

	<!-- TOP BAR -->
	<div class="top-bar">
    <div class="top-bar-track">
        <span>LIVRAISON OFFERTE DÈS 49€</span>
        <span>RETOURS GRATUITS</span>
        <span>PAIEMENT SÉCURISÉ</span>
    </div>
	</div>


	<!-- MAIN NAV -->
	<nav class="navbar navbar-expand-lg header-nav">
		<div class="container d-flex align-items-center justify-content-between">

			<!-- LOGO -->
			<a class="navbar-brand fw-bold" href="<?= home_url(); ?>">Change Me</a>

			<!-- BURGER -->
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainMenu">
				<span class="navbar-toggler-icon"></span>
			</button>

			<!-- MENU WordPress -->
			<div class="collapse navbar-collapse" id="mainMenu">
				<?php
					wp_nav_menu([
						'theme_location' => 'header_menu',
						'container'      => false,
						'menu_class'     => 'main-menu navbar-nav mx-auto',
						'fallback_cb'    => false,
					]);
				?>
			</div>

			<!-- ICONS -->
			<div class="header-icons d-flex align-items-center gap-3">
				<a class="icon search-icon" href="#">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M21.0002 21.0002L16.6572 16.6572M16.6572 16.6572C17.4001 15.9143 17.9894 15.0324 18.3914 14.0618C18.7935 13.0911 19.0004 12.0508 19.0004 11.0002C19.0004 9.9496 18.7935 8.90929 18.3914 7.93866C17.9894 6.96803 17.4001 6.08609 16.6572 5.34321C15.9143 4.60032 15.0324 4.01103 14.0618 3.60898C13.0911 3.20693 12.0508 3 11.0002 3C9.9496 3 8.90929 3.20693 7.93866 3.60898C6.96803 4.01103 6.08609 4.60032 5.34321 5.34321C3.84288 6.84354 3 8.87842 3 11.0002C3 13.122 3.84288 15.1569 5.34321 16.6572C6.84354 18.1575 8.87842 19.0004 11.0002 19.0004C13.122 19.0004 15.1569 18.1575 16.6572 16.6572Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</a>
				<a class="icon user-icon" href="/my-account">
					<svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M14.4329 13.2502C13.481 11.6046 12.0142 10.4246 10.3023 9.86524C11.1491 9.36115 11.8069 8.59303 12.1749 7.67886C12.5429 6.76468 12.6006 5.75499 12.3392 4.80483C12.0778 3.85468 11.5117 3.01661 10.7279 2.41931C9.94408 1.82202 8.98587 1.49854 8.00041 1.49854C7.01496 1.49854 6.05674 1.82202 5.27293 2.41931C4.48911 3.01661 3.92304 3.85468 3.66163 4.80483C3.40022 5.75499 3.45793 6.76468 3.82591 7.67886C4.19388 8.59303 4.85177 9.36115 5.69854 9.86524C3.98666 10.424 2.51979 11.604 1.56791 13.2502C1.53301 13.3072 1.50985 13.3705 1.49982 13.4365C1.48978 13.5025 1.49307 13.5699 1.50949 13.6346C1.52591 13.6993 1.55512 13.7601 1.59541 13.8133C1.63569 13.8666 1.68624 13.9112 1.74405 13.9446C1.80187 13.978 1.86579 13.9995 1.93204 14.0078C1.9983 14.0161 2.06554 14.011 2.1298 13.9929C2.19407 13.9748 2.25405 13.944 2.30622 13.9023C2.35838 13.8606 2.40168 13.8089 2.43354 13.7502C3.61104 11.7152 5.69229 10.5002 8.00041 10.5002C10.3085 10.5002 12.3898 11.7152 13.5673 13.7502C13.5992 13.8089 13.6424 13.8606 13.6946 13.9023C13.7468 13.944 13.8068 13.9748 13.871 13.9929C13.9353 14.011 14.0025 14.0161 14.0688 14.0078C14.135 13.9995 14.199 13.978 14.2568 13.9446C14.3146 13.9112 14.3651 13.8666 14.4054 13.8133C14.4457 13.7601 14.4749 13.6993 14.4913 13.6346C14.5078 13.5699 14.511 13.5025 14.501 13.4365C14.491 13.3705 14.4678 13.3072 14.4329 13.2502ZM4.50041 6.00024C4.50041 5.308 4.70568 4.63131 5.09027 4.05574C5.47485 3.48017 6.02148 3.03156 6.66102 2.76666C7.30056 2.50175 8.0043 2.43244 8.68323 2.56749C9.36216 2.70254 9.9858 3.03588 10.4753 3.52536C10.9648 4.01485 11.2981 4.63849 11.4332 5.31742C11.5682 5.99635 11.4989 6.70009 11.234 7.33963C10.9691 7.97917 10.5205 8.52579 9.94491 8.91038C9.36934 9.29496 8.69265 9.50024 8.00041 9.50024C7.07246 9.49924 6.1828 9.13018 5.52664 8.47401C4.87047 7.81785 4.50141 6.92819 4.50041 6.00024Z" fill="currentColor"/>
					</svg>
				</a>
				<a class="icon cart-icon" href="/cart">
					<svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M5 4.5C5 4.5 5 1.5 8 1.5C11 1.5 11 4.5 11 4.5M2.5 4.5V14.5H13.5V4.5H2.5Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</a>
			</div>

		</div>
	</nav>

</header>

<main class="site-content">
