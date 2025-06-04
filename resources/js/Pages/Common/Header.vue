<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
</script>
<template> 
			<div class="main-header">
				<div class="main-header-logo">

					<!-- Logo Header -->
					<div class="logo-header" data-background-color="dark">
						<a href="index.html" class="logo">
							<img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand"
								height="20">
						</a>
						<div class="nav-toggle">
							<button class="btn btn-toggle toggle-sidebar">
								<i class="gg-menu-right"></i>
							</button>
							<button class="btn btn-toggle sidenav-toggler">
								<i class="gg-menu-left"></i>
							</button>
						</div>
						<button class="topbar-toggler more">
							<i class="gg-more-vertical-alt"></i>
						</button>
					</div>

					<!-- End Logo Header -->
				</div>

				<!-- Navbar Header -->
				<nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
					<div class="container-fluid">

						<ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
							<li class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none">
								<a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button"
									aria-expanded="false" aria-haspopup="true">
									<i class="fa fa-search"></i>
								</a>
								<ul class="dropdown-menu dropdown-search animated fadeIn">
									<form class="navbar-left navbar-form nav-search">
										<div class="input-group">
											<input type="text" placeholder="Search ..." class="form-control">
										</div>
									</form>
								</ul>
							</li>
							<li class="nav-item topbar-icon dropdown hidden-caret">
								<!-- <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button"
									data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<i class="fa fa-bell"></i>
									<span class="notification">4</span>
								</a> -->

								<!-- <ul
                    class="dropdown-menu notif-box animated fadeIn"
                    aria-labelledby="notifDropdown"
                  >
                    <li>
                      <div class="dropdown-title">
                        You have 4 new notification
                      </div>
                    </li>
                    <li>
                      <div class="notif-scroll scrollbar-outer">
                        <div class="notif-center">
                          <a href="#">
                            <div class="notif-icon notif-primary">
                              <i class="fa fa-user-plus"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block"> New user registered </span>
                              <span class="time">5 minutes ago</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-icon notif-success">
                              <i class="fa fa-comment"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block">
                                Rahmad commented on Admin
                              </span>
                              <span class="time">12 minutes ago</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-img">
                              <img
                                src="assets/img/profile2.jpg"
                                alt="Img Profile"
                              />
                            </div>
                            <div class="notif-content">
                              <span class="block">
                                Reza send messages to you
                              </span>
                              <span class="time">12 minutes ago</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-icon notif-danger">
                              <i class="fa fa-heart"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block"> Farrah liked Admin </span>
                              <span class="time">17 minutes ago</span>
                            </div>
                          </a>
                        </div>
                      </div>
                    </li>
                    <li>
                      <a class="see-all" href="javascript:void(0);"
                        >See all notifications<i class="fa fa-angle-right"></i>
                      </a>
                    </li>
                  </ul> -->
				  				<div class="avatar-sm">
									<img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle">
								</div>
							</li>

							<li class="nav-item topbar-user dropdown hidden-caret">
								<!-- <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#"
									aria-expanded="false"> -->
									<!-- <div class="avatar-sm">
										<img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle">
									</div> -->
									<!-- <span class="profile-username">
										<span class="fw-bold">{{ $page.props.auth.user.name }}</span>
									</span>
								</a>
								<ul class="dropdown-menu dropdown-user animated fadeIn">
									<div class="dropdown-user-scroll scrollbar-outer">
										<li>
											<div class="user-box">
												<div class="avatar-lg">
													<img src="assets/img/profile.jpg" alt="image profile"
														class="avatar-img rounded">
												</div>
												<div class="u-text">
													<h4>Akash Rana</h4>
													<p class="text-muted">xyz@gmail.com</p>
													<a href="#" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
												</div>
											</div>
										</li>
										<li>
											<div class="dropdown-divider"></div>
											<a class="dropdown-item" href="#">My Profile</a>
											<div class="dropdown-divider"></div>
											<a class="dropdown-item" href="login.html">Logout</a>
										</li>
									</div>
								</ul> -->

								

								<!-- <div class="hidden sm:ms-6 sm:flex sm:items-center"> -->
								<div class="hidden sm:flex sm:items-center">
									<!-- Settings Dropdown -->
									<div class="relative ms-1">
										<Dropdown align="right" width="48">
											<template #trigger>

												

												<span class="inline-flex rounded-md">
													<button
														type="button"
														class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
													>
														{{ $page.props.auth.user.name }}

														<svg
															class="-me-0.5 ms-2 h-4 w-4"
															xmlns="http://www.w3.org/2000/svg"
															viewBox="0 0 20 20"
															fill="currentColor"
														>
															<path
																fill-rule="evenodd"
																d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
																clip-rule="evenodd"
															/>
														</svg>
													</button>
												</span>
											</template>

											<template #content>
												<DropdownLink
													:href="route('profile.edit')"
												>
													Profile
												</DropdownLink>
												<DropdownLink
													:href="route('logout')"
													method="post"
													as="button"
												>
													Log Out
												</DropdownLink>
											</template>
										</Dropdown>
									</div>
								</div>
								
							</li>
						</ul>
					</div>
				</nav>

				<!-- End Navbar -->
			</div>
</template>