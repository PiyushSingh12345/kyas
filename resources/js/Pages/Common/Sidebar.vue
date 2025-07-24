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
                    <li><Link :href="route('budget-head-list')" class="nav-link">
                      <span class="sub-item">Budget Heads</span>
                    </Link></li>
                    <li><Link :href="route('budget-phase')" class="nav-link"><span class="sub-item">Budget Phase</span></Link></li>
                    <li><Link :href="route('state-uts')" class="nav-link"><span class="sub-item">State/UTs</span></Link></li>
                    <li><Link :href="route('state-uts-pd')" class="nav-link"><span class="sub-item">States/UTs-PD/Component</span></Link></li>
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
                      <Link :href="route('fund-allocation')" class="nav-link">
                        <span class="sub-item">Fund Allocation</span>
                      </Link>
                    </li>
                  </ul>
                </div>
              </li>
              
              <!-- Mother Sanction Module-->
              <li class="nav-item" :class="{ active: activeMenu === 'mother-sanction' }" v-if="hasRole([2])">
                <Link :href="route('mother-sanction')" class="nav-link">
                  <i class="fas fa-pen-square"></i>
                  <p>Mother Sanction</p>
                </Link>
              </li>
              <!-- Daily Sanction Module -->
              <li class="nav-item" :class="{ active: activeMenu === 'daily-sanction' }" v-if="hasRole([2])">
                    <Link :href="route('daily-sanction-list')" class="nav-link">
                      <i class="fas fa-pen-square"></i>
                      <p>Daily Sanction</p>
                    </Link>
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
                    <li><Link :href="route('budget-phase-report')" class="nav-link"><span class="sub-item">Budget phases Summary Report</span></Link></li>
                    <li><Link :href="route('mother-sanction-report')" class="nav-link"><span class="sub-item">Mother Sanction Summary</span></Link></li>
                    <li><Link :href="route('fund-allocation-report')" class="nav-link"><span class="sub-item">Fund Allocation Report</span></Link></li>
                    <li><Link :href="route('rog-report')" class="nav-link"><span class="sub-item">RoG Report</span></Link></li>

                    <li><Link :href="route('re-appropriation-mis-report')" class="nav-link"><span class="sub-item">Re-Appropriation of MIS Report</span></Link></li>
                    <li><Link :href="route('mother-sanction-list')" class="nav-link"><span class="sub-item">Mother Sanction List</span></Link></li>
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
import { Link, usePage } from '@inertiajs/vue3'


const userTypeIds = usePage().props.auth.user_type_ids || []

const hasRole = (roles) => {
  const hasAccess = roles.some(roleId => userTypeIds.includes(Number(roleId)))
  return hasAccess
}

const activeMenu = ref('') // This will store the ID of the currently open menu

// Toggle menu on click
const toggleMenu = (menuId) => {
  activeMenu.value = activeMenu.value === menuId ? '' : menuId
}

const path = window.location.pathname

const menuMap = {
  '/user-listing': 'dashboard',
  '/user-create': 'dashboard',
  '/budget-phase': 'bhead',
  // '/budget-phase': 'budget',
  '/fund-allocation': 'budget',
  '/state-uts': 'bhead',
  '/state-uts-pd': 'bhead',
  '/daily-sanction-list': 'daily-sanction', // or a separate 'daily-sanction' if you reopen it
  '/mother-sanction-list': 'mother-sanction',
  '/budget-phase-report': 'reports',
  // '/budget-phase': 'reports',
  '/state-uts': 'reports',
  '/state-uts-pd': 'mother-sanction',
}





onMounted(() => {
  // console.log('User Type IDs:', userTypeIds);
  // const current = window.location.pathname
  activeMenu.value = menuMap[path] || ''  // fallback: no menu open

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
</style>

