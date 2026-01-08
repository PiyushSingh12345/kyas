<template>
  <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-logo">

      <!-- Sidebar -->
      <div class="sidebar">
        <div class="sidebar-logo">

          <!-- Logo Header -->
          <div class="logo-header">
            <a href="/user-listing" class="logo">
              KY Automation System
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
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">

              <li class="nav-item" :class="{ active: activeMenu === 'dashboard' }" v-if="hasRole([1])">
                <a href="#" @click.prevent="toggleMenu('dashboard')">
                  <i class="fas fa-home"></i>
                  <p>User Management</p>
                  <span class="caret" :class="{ rotated: activeMenu === 'dashboard' }"></span>
                </a>

                <div v-show="activeMenu === 'dashboard'">
                  <ul class="nav nav-collapse">

                    <!-- <li>
                      <a href="/user-listing">
                        <span class="sub-item">Dashboard</span>
                      </a>
                    </li> -->


                    <li>
                      <a href="/user-create">
                        <span class="sub-item">Create User</span>
                      </a>
                    </li>
                    <li>
                      <a href="/user-listing">
                        <span class="sub-item">Update/Delete User</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>

              <!-- Budget Head -->
              <li class="nav-item" :class="{ active: activeMenu === 'bhead' }" v-if="hasRole([2,3])">
                <a href="#" @click.prevent="toggleMenu('bhead')">
                  <i class="fas fa-layer-group"></i>
                  <p>Budget Head</p>
                  <span class="caret" :class="{ rotated: activeMenu === 'bhead' }"></span>
                </a>
                <div v-show="activeMenu === 'bhead'">
                  <ul class="nav nav-collapse">
                    <li><Link :href="route('budget-heads')" class="nav-link">
                      <span class="sub-item">Budget Heads</span>
                    </Link></li>
                    <li><Link :href="route('budget-phase')" class="nav-link"><span class="sub-item">Budget Phase</span></Link></li>
                    <!-- <li><Link :href="route('budget-phase-history')" class="nav-link"><span class="sub-item">Budget Phase History</span></Link></li> -->
                    <li><Link :href="route('state-uts')" class="nav-link"><span class="sub-item">State/UTs</span></Link></li>
                    <li><Link :href="route('state-uts-pd')" class="nav-link"><span class="sub-item">PD Component / SLS</span></Link></li>
                  </ul>
                </div>
              </li>

              <!-- Budget Allocation Module -->
              <li class="nav-item" :class="{ active: activeMenu === 'budget' }" v-if="hasRole([2])">
                <a href="#" @click.prevent="toggleMenu('budget')">
                  <i class="fas fa-pen-square"></i>
                  <p>Budget Allocation</p>
                  <span class="caret" :class="{ rotated: activeMenu === 'budget' }"></span>
                </a>
                <div v-show="activeMenu === 'budget'">
                  <ul class="nav nav-collapse">
                    <!-- <li>
                      <Link :href="route('budget-phase')" class="nav-link">
                        <span class="sub-item">Add details of BE/FE/RE</span>
                      </Link>
                    </li> -->
                    <li>
                      <Link :href="route('pd-wise-budget-allocation')" class="nav-link">
                        <span class="sub-item">PD wise Budget Allocation</span>
                      </Link>
                    </li>
                    <li>
                      <Link :href="route('pd-wise-budget-allocation-release')" class="nav-link">
                        <span class="sub-item">PD wise Budget Allocation Release</span>
                      </Link>
                    </li>
                    <!-- <li>
                      <Link :href="route('fund-allocation')" class="nav-link">
                        <span class="sub-item">Fund Allocation</span>
                      </Link>
                    </li> -->
                  </ul>
                </div>
              </li>
              
              <!-- Mother Sanction Module-->
              <!-- <li class="nav-item" :class="{ active: activeMenu === 'mother-sanction' }" v-if="hasRole([2])">
                <Link :href="route('mother-sanction')" class="nav-link">
                  <i class="fas fa-pen-square"></i>
                  <p>Mother Sanction</p>
                </Link>
              </li> -->
              <li class="nav-item" :class="{ active: activeMenu === 'modulemothersanction' }" v-if="hasRole([2])">
                <a href="#" @click.prevent="toggleMenu('module-mother-sanction')">
                  <i class="fas fa-pen-square"></i>
                  <p>Mother Sanction Module</p>
                  <span class="caret" :class="{ rotated: activeMenu === 'module-mother-sanction' }"></span>
                </a>
                <div v-show="activeMenu === 'module-mother-sanction'">
                  <ul class="nav nav-collapse">
                    <li>
                      <Link :href="route('mother-sanction')" class="nav-link">
                        <span class="sub-item">Mother Sanction</span>
                      </Link>
                    </li>
                    <li>
                      <Link :href="route('mother-sanction-list-module')" class="nav-link">
                        <span class="sub-item">Mother Sanction List</span>
                      </Link>
                    </li>
                    <li>
                      <Link :href="route('mother-sanction-history')" class="nav-link">
                        <span class="sub-item">Mother Sanction History</span>
                      </Link>
                    </li>
                  </ul>
                </div>
              </li>
              <!-- Daily Sanction Module -->
              <!-- <li class="nav-item" :class="{ active: activeMenu === 'daily-sanction' }" v-if="hasRole([2])">
                    <Link :href="route('daily-sanction-list')" class="nav-link">
                      <i class="fas fa-pen-square"></i>
                      <p>Daily Sanction</p>
                    </Link>
              </li> -->
              <li class="nav-item" :class="{ active: activeMenu === 'moduledailysanction' }" v-if="hasRole([2])">
                <a href="#" @click.prevent="toggleMenu('moduledailysanction')">
                  <i class="fas fa-pen-square"></i>
                  <p>Daily Sanction Module</p>
                  <span class="caret" :class="{ rotated: activeMenu === 'moduledailysanction' }"></span>
                </a>
                <div v-show="activeMenu === 'moduledailysanction'">
                  <ul class="nav nav-collapse">
                    <li>
                      <Link :href="route('daily-sanction')" class="nav-link">
                        <span class="sub-item">Daily Sanction</span>
                      </Link>
                    </li>
                    <li>
                      <Link :href="route('daily-sanction-list')" class="nav-link">
                        <span class="sub-item">Daily Sanction List</span>
                      </Link>
                    </li>
                  </ul>
                </div>
              </li>

              <!-- Re-Appropriation Module -->
              <li class="nav-item" :class="{ active: activeMenu === 're-appropriation-of-funds' }" v-if="hasRole([2])">
                    <Link :href="route('re-appropriation-of-funds')" class="nav-link">
                      <i class="fas fa-pen-square"></i>
                      <p>Re-Appropriation</p>
                    </Link>
              </li>

              <!-- Re-Appropriation Module -->
              <!-- <li class="nav-item" :class="{ active: activeMenu === 're-appropriation-of-funds' }" v-if="hasRole([2])">
                <a href="#" @click.prevent="toggleMenu('re-appropriation-of-funds')">
                  <i class="fas fa-pen-square"></i>
                  <p>Re-Appropriation</p>
                  <span class="caret" :class="{ rotated: activeMenu === 're-appropriation-of-funds' }"></span>
                </a>
                <div v-show="activeMenu === 're-appropriation-of-funds'">
                  <ul class="nav nav-collapse">
                    <li>
                      <Link :href="route('re-appropriation-of-funds')" class="nav-link">
                        <span class="sub-item">Re-Appropriation of Funds</span>
                      </Link>
                    </li>        
                  </ul>
                </div>
              </li> -->

              <li class="nav-item" :class="{ active: activeMenu === 'reports' }" v-if="hasRole([1,2,4])">
                <a href="#" @click.prevent="toggleMenu('reports')">
                  <i class="fas fa-layer-group"></i>
                  <p>MIS Reports &amp; Dashboards</p>
                  <span class="caret" :class="{ rotated: activeMenu === 'reports' }"></span>
                </a>
                <div v-show="activeMenu === 'reports'">
                   <ul class="nav nav-collapse">
                    <li><Link :href="route('budget-phase-report-a')" class="nav-link"><span class="sub-item">Budget phases Report</span></Link></li>
                    <li><Link :href="route('pd-wise-budget-allocation-report')" class="nav-link"><span class="sub-item">PD wise Budget Allocation Report</span></Link></li>
                    <li><Link :href="route('daily-sanction-reports')" class="nav-link"><span class="sub-item">Daily Sanction Report</span></Link></li>
                    <li><Link :href="route('mother-sanction-reports')" class="nav-link"><span class="sub-item">Mother Sanction Report</span></Link></li>
                    <!-- <li><Link :href="route('daily-sanction-report')" class="nav-link"><span class="sub-item">Daily Sanction Report</span></Link></li> -->
                    <!--<li><Link :href="route('mother-sanction-report')" class="nav-link"><span class="sub-item">Mother Sanction Report</span></Link></li> -->
                   <!-- <li><Link :href="route('budget-phase-report')" class="nav-link"><span class="sub-item">Budget phases Summary Report</span></Link></li>
                    <li><Link :href="route('mother-sanction-report')" class="nav-link"><span class="sub-item">Mother Sanction Summary</span></Link></li>
                    <li><Link :href="route('fund-allocation-report')" class="nav-link"><span class="sub-item">Fund Allocation Report</span></Link></li>
                    <li><Link :href="route('rog-report')" class="nav-link"><span class="sub-item">RoG Report</span></Link></li>

                    <li><Link :href="route('re-appropriation-mis-report')" class="nav-link"><span class="sub-item">Re-Appropriation of MIS Report</span></Link></li>
                    <li><Link :href="route('mother-sanction-list')" class="nav-link"><span class="sub-item">Mother Sanction List</span></Link></li>
                    <li><Link :href="route('daily-sanction-time-series-report')" class="nav-link"><span class="sub-item">Daily Sanction Report</span></Link></li>
                    <li><Link :href="route('mother-sanction-time-series-report')" class="nav-link"><span class="sub-item">Mother Sanction Report</span></Link></li>-->
                  </ul>
                </div>
              </li>

              <!-- Role-Based Access Demo -->
              <!-- <li class="nav-item" :class="{ active: activeMenu === 'role-demo' }" v-if="hasRole([1])">
                <Link :href="route('role-based-access-demo')" class="nav-link">
                  <i class="fas fa-shield-alt"></i>
                  <p>Role-Based Access Demo</p>
                </Link>
              </li> -->

              <!-- Annual Action plan Module -->
              <li class="nav-item" :class="{ active: activeMenu === 'annual-action-plan' }" v-if="hasRole([2])">
                <a href="#" @click.prevent="toggleMenu('annual-action-plan')">
                  <i class="fas fa-pen-square"></i>
                  <p>Annual Action Plan</p>
                  <span class="caret" :class="{ rotated: activeMenu === 'annual-action-plan' }"></span>
                </a>
                <div v-show="activeMenu === 'annual-action-plan'">
                  <ul class="nav nav-collapse">
                    <li>
                      <Link :href="route('statewise-aap-allocation')" class="nav-link">
                        <span class="sub-item">Statewise AAP Allocation</span>
                      </Link>
                    </li>
                    <li>
                      <Link :href="route('state-release-data')" class="nav-link">
                        <span class="sub-item">State Release Data</span>
                      </Link>
                    </li>
                    <!-- <li>
                      <Link :href="route('pd-wise-budget-allocation')" class="nav-link">
                        <span class="sub-item">PD wise Budget Allocation</span>
                      </Link>
                    </li> -->
                  </ul>
                </div>
              </li>

              <!-- Agency Release Module -->
              <li class="nav-item" :class="{ active: activeMenu === 'agency-release' }" v-if="hasRole([2])">
                <a href="#" @click.prevent="toggleMenu('agency-release')">
                  <i class="fas fa-pen-square"></i>
                  <p>Agency Release</p>
                  <span class="caret" :class="{ rotated: activeMenu === 'agency-release' }"></span>
                </a>
                <div v-show="activeMenu === 'agency-release'">
                  <ul class="nav nav-collapse">
                    <li>
                      <Link :href="route('agency-release-tsa')" class="nav-link">
                        <span class="sub-item">TSA</span>
                      </Link>
                    </li>
                    <li>
                      <Link :href="route('agency-release-tsa-list')" class="nav-link">
                        <span class="sub-item">TSA List</span>
                      </Link>
                    </li>
                    <li>
                      <Link :href="route('agency-release-loa')" class="nav-link">
                        <span class="sub-item">LOA</span>
                      </Link>
                    </li>
                    <li>
                      <Link :href="route('agency-release-loa-list')" class="nav-link">
                        <span class="sub-item">LOA List</span>
                      </Link>
                    </li>
                    <li>
                      <Link :href="route('agency-release-administrative-expenditure')" class="nav-link">
                        <span class="sub-item">Administrative Expenditure</span>
                      </Link>
                    </li>
                    <li>
                      <Link :href="route('agency-release-administrative-expenditure-list')" class="nav-link">
                        <span class="sub-item">Administrative Expenditure List</span>
                      </Link>
                    </li>
                  </ul>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- End Sidebar -->
      </div>
    </div>



            

            
         
            <!-- </ul>
          </div>
        </div>
      </div> -->

      <!-- End Sidebar -->
      <!-- </div>
    </div> -->
</template>

<script setup>

import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useRoleAccess } from '../../Composables/useRoleAccess'

const { hasRole } = useRoleAccess()

const activeMenu = ref('') // This will store the ID of the currently open menu

// Toggle menu on click
const toggleMenu = (menuId) => {
  activeMenu.value = activeMenu.value === menuId ? '' : menuId
}

const path = window.location.pathname

const menuMap = {
  '/user-listing': 'dashboard',
  '/user-create': 'dashboard',
  '/budget-heads': 'bhead',
  '/budget-phase': 'bhead',
  '/budget-phase-history': 'bhead',
  '/fund-allocation': 'budget',
  '/state-uts': 'bhead',
  '/state-uts-pd': 'bhead',
  '/daily-sanction': 'moduledailysanction',
  '/daily-sanction-list': 'moduledailysanction',
  '/mother-sanction': 'module-mother-sanction',
  '/mother-sanction-list': 'module-mother-sanction',
  '/mother-sanction-list-module': 'module-mother-sanction',
  '/mother-sanction-history': 'module-mother-sanction',
  '/budget-phase-report': 'reports',
  '/mother-sanction-report': 'reports',
  '/fund-allocation-report': 'reports',
  '/rog-report': 'reports',
  '/re-appropriation-mis-report': 'reports',
  '/daily-sanction-time-series-report': 'reports',
  '/mother-sanction-time-series-report': 'reports',
  '/re-appropriation-of-funds': 're-appropriation-of-funds',
  '/statewise-aap-allocation': 'annual-action-plan',
  '/state-release-data': 'annual-action-plan',
  '/pd-wise-budget-allocation': 'budget',
  '/agency-release-tsa': 'agency-release',
  '/agency-release-tsa-list': 'agency-release',
  '/agency-release-loa': 'agency-release',
  '/agency-release-loa-list': 'agency-release',
  '/agency-release-administrative-expenditure': 'agency-release',
  '/agency-release-administrative-expenditure-list': 'agency-release',
}





onMounted(() => {
  // Set active menu based on current path
  activeMenu.value = menuMap[path] || ''
  
  // Add active class to current submenu item
  const currentPath = window.location.pathname
  document.querySelectorAll('.nav-collapse .nav-link').forEach(link => {
    const href = link.getAttribute('href')
    if (href === currentPath) {
      link.classList.add('active')
    } else {
      link.classList.remove('active')
    }
  })

  document.querySelectorAll('.collapse').forEach(el => {
    new Collapse(el, { toggle: false })
  })
})
</script>


<!-- style css code  -->
<style>
.sidebar-logo .logo-header .logo {
    width: 100%;
}

/* Active menu styling */
.nav-item.active > a {
    color: #1269db !important;
}

.nav-item.active > a i {
    color: #1269db !important;
}

.nav-item.active > a p {
    color: #1269db !important;
}

/* Active submenu item styling */
.nav-collapse .nav-link.active {
    color: #1269db !important;
}

.nav-collapse .nav-link.active .sub-item {
    color: #1269db !important;
}

/* Hover effects for menu items */
.nav-item > a:hover {
    color: #1269db !important;
}

.nav-item > a:hover i {
    color: #1269db !important;
}

.nav-item > a:hover p {
    color: #1269db !important;
}

/* Submenu hover effects */
.nav-collapse .nav-link:hover {
    color: #1269db !important;
}

.nav-collapse .nav-link:hover .sub-item {
    color: #1269db !important;
}

/* Caret rotation for active menu */
.nav-item.active .caret.rotated {
    transform: rotate(90deg);
    transition: transform 0.3s ease;
}

/* Smooth transitions */
.nav-item > a,
.nav-item > a i,
.nav-item > a p,
.nav-collapse .nav-link,
.nav-collapse .nav-link .sub-item {
    transition: color 0.3s ease;
}
</style>

