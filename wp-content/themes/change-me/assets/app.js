/**
 * Created by T. CHANET
 * @author Genesii SAS
 * @version 1.0
 */

import $ from 'jquery';
//import jQueryBridget from 'jquery-bridget';

//Filtering and Sorting
//import Isotope from 'isotope-layout';

//Cookies JS
//import Cookies from 'js-cookie'

//Formating input
//import AutoNumeric from 'autonumeric';

//Animations
//import AOS from 'aos';

//OWL Carousel
//import 'owl.carousel/dist/assets/owl.carousel.css';
//import 'owl.carousel';

//Bootstrap
import 'bootstrap';
import { createPopper } from '@popperjs/core';

//jQuery adds
require('webpack-jquery-ui');
//require('jquery-ui-touch-punch');

//Multi touch
//require('hammerjs');

/*----------------------------*/
//SCSS
require("./app.scss");
/*----------------------------*/

$(document).ready(function($){
    //init animations AOS
    //AOS.init();

    console.log("%cCreated by Genesii and Gardeners, with ❤", "color: #9ed6a8; padding: 5px 0px 1px; border-bottom:2px solid #9ed6a8;");
});

/*----------------------------*/
//Extra JS -> write here
require('./js/script');
/*----------------------------*/

