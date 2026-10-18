<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>@yield('title', $hotel?->name ?? 'Hotel') - @yield('page_title', 'Luxury Hotel')</title>
    <meta name="description" content="@yield('meta_description', '')" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Favicon -->
    @php
        $faviconUrl = ($hotel && $hotel->favicon_path) ? asset('storage/' . $hotel->favicon_path) : asset('frontend/assets/images/fav-icon/icon.png');
    @endphp
    <link rel="icon" type="image/png" sizes="56x56" href="{{ $faviconUrl }}" />
    <!-- bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/bootstrap.min.css') }}" type="text/css" media="all" />
    <!-- carousel CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/owl.carousel.min.css') }}" type="text/css" media="all" />
    <!-- font-awesome CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/all.min.css') }}" type="text/css" media="all" />
    <!-- font-flaticon CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/flaticon.css') }}" type="text/css" media="all" />
    <!-- theme-default CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/theme-default.css') }}" type="text/css" media="all" />
    <!-- meanmenu CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/meanmenu.min.css') }}" type="text/css" media="all" />
    <!-- venobox CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/venobox/venobox.css') }}" type="text/css" media="all" />
    <!-- bootstrap icons -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/bootstrap-icons.css') }}" type="text/css" media="all" />
    <!-- Main Style CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/style.css') }}" type="text/css" media="all" />
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/odometer-theme-default.css') }}" />
    <!-- responsive CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/responsive.css') }}" type="text/css" media="all" />
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/swiper.min.css') }}" />
    <!-- modernizr js -->
    <script src="{{ asset('frontend/assets/js/vendor/modernizr-3.5.0.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/aos.css') }}" />
    @yield('css')
</head>
<body>
    <!-- loder -->
    <div class="loader-wrapper">
        <div class="loader"></div>
        <div class="loder-section left-section"></div>
        <div class="loder-section right-section"></div>
    </div>

    <!--==================================================-->
    <!-- Start hotelhub Topber Area -->
    <!--==================================================-->
    <div class="topber_area">
        <div class="container-fluid">
            <div class="row topber_upper align-items-center d-flex">
                <div class="col-lg-6">
                    <div class="topber-text">
                        <p><span>Hello</span> Welcome to {{ $hotel?->name ?? 'Our Luxury Hotel' }}</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="topber-social-icon">
                        <h4 class="topber-follow">Follow Us</h4>
                        <a href="#">fb</a>
                        <a href="#">wt-x</a>
                        <a href="#">in</a>
                        <a href="#">ln</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--==================================================-->
    <!-- End hotelhub Topber Area -->
    <!--==================================================-->

    <!--==================================================-->
    <!-- Start hotelhub Main Menu  -->
    <!--==================================================-->
    <div id="sticky-header" class="hotelhub_nav_manu @hasSection('is_home') @else two inner_page @endif" style="background: #1a1a1a; position: relative;">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-3">
                    <div class="logo cursor-scale small">
                        <a class="logo_img" href="{{ route('public.index') }}" title="{{ $hotel?->name ?? 'hotelhub' }}">
                            @if($hotel && $hotel->logo_path)
                                <img loading="lazy" src="{{ asset('storage/' . $hotel->logo_path) }}" alt="{{ $hotel->name }}" style="max-height: 50px;" />
                            @else
                                <img loading="lazy" src="{{ asset('frontend/assets/images/logo.png') }}" alt="logo" />
                            @endif
                        </a>
                    </div>
                </div>
                <div class="col-lg-9">
                    <nav class="meedy_menu">
                        <ul class="nav_scroll">
                            <li><a href="{{ route('public.index') }}">Home</a></li>
                            <li><a href="{{ route('public.rooms.index') }}">Rooms</a></li>
                            <li><a href="{{ route('public.services') }}">Services</a></li>
                            <li><a href="{{ route('public.gallery') }}">Gallery</a></li>
                            <li><a href="{{ route('public.about') }}">About</a></li>
                            <li><a href="{{ route('public.contact') }}">Contact</a></li>
                        </ul>
                        <div class="hotelhub-right-side cursor-scale small">
                            {{-- Branch Selector --}}
                            @php
                                $branches = \App\Models\Hotel::where('is_active', true)->get();
                                $currentBranch = $hotel;
                            @endphp
                            @if($branches->count() > 1)
                            <div style="margin-right: 15px; position: relative; display: inline-block;">
                                <a href="javascript:void(0);" id="branchToggle" style="color: #fff; text-decoration: none; font-size: 13px;">
                                    <i class="bi bi-geo-alt"></i> {{ Str::limit($currentBranch->name ?? 'Select Branch', 20) }} ▾
                                </a>
                                <div id="branchDropdown" style="display: none; position: absolute; top: 30px; right: 0; background: #fff; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); min-width: 220px; z-index: 9999; overflow: hidden;">
                                    @foreach($branches as $branch)
                                    <a href="{{ route('public.set-branch', $branch->id) }}" style="display: block; padding: 10px 15px; color: #333; text-decoration: none; font-size: 14px; border-bottom: 1px solid #f0f0f0; {{ $currentBranch && $currentBranch->id == $branch->id ? 'background: #8b7355; color: #fff;' : '' }}">
                                        {{ $branch->name }} @if($currentBranch && $currentBranch->id == $branch->id) ✓ @endif
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                            <script>
                                document.getElementById('branchToggle').addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    var dd = document.getElementById('branchDropdown');
                                    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
                                });
                                document.addEventListener('click', function() {
                                    document.getElementById('branchDropdown').style.display = 'none';
                                });
                            </script>
                            @endif
                            <div class="search-box-btn search-box-outer">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <!-- header button -->
                            <div class="header-button">
                                @auth('guest')
                                    <a href="{{ route('guest.dashboard') }}">My Account <i class="flaticon flaticon-right-arrow"></i>
                                @else
                                    <a href="{{ route('guest.login') }}">Login / Register <i class="flaticon flaticon-right-arrow"></i>
                                @endauth
                                    <div class="hotelhub-hover-btn hover-btn"></div>
                                    <div class="hotelhub-hover-btn hover-btn2"></div>
                                    <div class="hotelhub-hover-btn hover-btn3"></div>
                                    <div class="hotelhub-hover-btn hover-btn4"></div>
                                </a>
                            </div>
                            <div class="sidebar">
                                <div class="nav-btn navSidebar-button">
                                    <span><i class="fa-solid fa-bars"></i></span>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- hotelhub Mobile Menu  -->
    <div class="mobile-menu-area sticky d-sm-block d-md-block d-lg-none">
        <div class="mobile-menu">
            <nav class="meedy_menu">
                <ul class="nav_scroll">
                    <li><a href="{{ route('public.index') }}">Home</a></li>
                    <li><a href="{{ route('public.rooms.index') }}">Rooms</a></li>
                    <li><a href="{{ route('public.services') }}">Services</a></li>
                    <li><a href="{{ route('public.gallery') }}">Gallery</a></li>
                    <li><a href="{{ route('public.about') }}">About</a></li>
                    <li><a href="{{ route('public.contact') }}">Contact</a></li>
                </ul>
            </nav>
        </div>
    </div>
    <!--==================================================-->
    <!-- End hotelhub Main Menu  -->
    <!--==================================================-->

    <!--==================================================-->
    <!-- PAGE CONTENT -->
    <!--==================================================-->
    @yield('content')
    <!--==================================================-->
    <!-- END PAGE CONTENT -->
    <!--==================================================-->

    <!--==================================================-->
    <!-- Start hotelhub Footer Section -->
    <!--==================================================-->
    <div class="footer-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="widget">
                        <div class="footer_widget">
                            <div class="company-logo">
                                <a href="{{ route('public.index') }}">
                                    @if($hotel && $hotel->logo_path)
                                        <img loading="lazy" src="{{ asset('storage/' . $hotel->logo_path) }}" alt="{{ $hotel->name }}" style="max-height: 50px;"/>
                                    @else
                                        <img loading="lazy" src="{{ asset('frontend/assets/images/logo.png') }}" alt="logo"/>
                                    @endif
                                </a>
                            </div>
                            <p>{{ $hotel?->name ?? 'Hotel' }} - Your gateway to comfort, luxury, and unmatched hospitality.</p>
                            <div class="hotelhub-social-icon">
                                <h3 class="follow-title">Follow Us On :</h3>
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fa-brands fa-linkedin-in"> </i></a>
                                <a href="#"><i class="bi bi-twitter"></i></a>
                                <a href="#"><i class="fab fa-pinterest-p"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <div class="widget widget-nav-menu">
                        <h4 class="widget-title">About Us</h4>
                        <div class="menu-quick-link-content">
                            <ul class="footer-menu">
                                <li><a href="{{ route('public.about') }}"> About Hotel </a></li>
                                <li><a href="{{ route('public.rooms.index') }}"> Rooms & Suites </a></li>
                                <li><a href="{{ route('public.services') }}"> Our Services </a></li>
                                <li><a href="{{ route('public.gallery') }}"> Gallery </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="widget widget-nav-menu">
                        <h4 class="widget-title">Useful Links</h4>
                        <div class="menu-quick-link-content">
                            <ul class="footer-menu">
                                <li><a href="{{ route('public.index') }}"> Home </a></li>
                                <li><a href="{{ route('public.booking.form') }}"> Booking </a></li>
                                @auth('guest')
                                    <li><a href="{{ route('guest.dashboard') }}"> My Account </a></li>
                                @else
                                    <li><a href="{{ route('guest.login') }}"> Guest Portal </a></li>
                                @endauth
                                <li><a href="{{ route('public.contact') }}"> Contact Us </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="widget hotelhub-footer_widget">
                        <h4 class="widget-title">Contact Info</h4>
                        <p class="widget-froms">{{ $hotel?->address ?? 'Hotel Address' }}</p>
                        <p><i class="bi bi-telephone-forward"></i> {{ $hotel?->phone ?? '+00 123 456 789' }}</p>
                    </div>
                </div>
            </div>
            <div class="row footer-btm d-flex align-items-center">
                <div class="col-lg-6 col-md-6">
                    <div class="hotelhub-company-desc">
                        <p>© Copyright {{ date('Y') }} {{ $hotel?->name ?? 'Hotel' }}. All Rights Reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--==================================================-->
    <!-- End hotelhub Footer Section -->
    <!--==================================================-->

    <!--==================================================-->
    <!-- Sidebar Cart Item -->
    <!--==================================================-->
    <div class="xs-sidebar-group info-group">
        <div class="xs-overlay xs-bg-black"></div>
        <div class="xs-sidebar-widget">
            <div class="sidebar-widget-container">
                <div class="widget-heading">
                    <a href="#" class="close-side-widget"><i class="far fa-times-circle"></i></a>
                </div>
                <div class="sidebar-textwidget">
                    <div class="sidebar-info-contents">
                        <div class="content-inner2">
                            <div class="nav-logo">
                                <a href="{{ route('public.index') }}">
                                    @if($hotel && $hotel->logo_path)
                                        <img loading="lazy" src="{{ asset('storage/' . $hotel->logo_path) }}" alt="{{ $hotel->name }}" style="max-height: 40px;" />
                                    @else
                                        <img loading="lazy" src="{{ asset('frontend/assets/images/logo2.png') }}" alt="" />
                                    @endif
                                </a>
                            </div>
                            <div class="contact-info">
                                <h2>Contact Info</h2>
                                <ul class="list-style-one">
                                    <li><span><i class="bi bi-envelope"></i></span>{{ $hotel?->address ?? 'Hotel Address' }}</li>
                                    <li><span><i class="bi bi-telephone-forward"></i></span>{{ $hotel?->phone ?? '+00 123 456 789' }}</li>
                                    <li><span><i class="bi bi-clock"></i></span>Week Days: 09.00 to 18.00 Sunday: Closed</li>
                                </ul>
                            </div>
                            <ul class="social-box">
                                <li class="facebook"><a href="#" class="fab fa-facebook-f"></a></li>
                                <li class="twitter"><a href="#" class="fab fa-instagram"></a></li>
                                <li class="linkedin"><a href="#" class="fab fa-twitter"></a></li>
                                <li class="instagram"><a href="#" class="fab fa-pinterest-p"></a></li>
                                <li class="youtube"><a href="#" class="fab fa-linkedin-in"></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--==================================================-->
    <!-- Start Search Popup Section -->
    <!--==================================================-->
    <div class="search-popup">
        <button class="close-search style-two"><span class="flaticon-multiply"><i class="far fa-times-circle"></i></span></button>
        <button class="close-search"><i class="bi bi-arrow-up"></i></button>
        <form method="post" data-ajax="true" action="#">
            <div class="form-group">
                <input type="search" name="search-field" value="" placeholder="Search Here" required="">
                <button type="submit" data-submit-protect="true"><i class="fa fa-search"></i></button>
            </div>
        </form>
    </div>
    <!--==================================================-->
    <!-- End Search Popup Section -->
    <!--==================================================-->

    <!--==================================================-->
    <!-- Start scrollup section Area -->
    <!--==================================================-->
    <div id="progress" class="progress hide">
        <div id="progress-value"></div>
    </div>
    <!--==================================================-->
    <!-- End scrollup section Area -->
    <!--==================================================-->

    <script src="{{ asset('frontend/assets/js/aos.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/vendor/jquery-3.6.2.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/odometer.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/gsap.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/imagesloaded.pkgd.min.js') }}"></script>
    <script src="{{ asset('frontend/venobox/venobox.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/jquery.meanmenu.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/jquery.scrollUp.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/appear.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/jquery.barfiller.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/swiper.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/theme.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/my.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/script.js') }}"></script>
    @yield('script')
</body>
</html>
