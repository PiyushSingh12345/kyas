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

              <li class="nav-item active">
                <!-- <a data-bs-toggle="collapse" href="/user-listing" class="collapsed" aria-expanded="false"> -->
                <!-- <a data-bs-toggle="collapse" href="#dashboard" class="collapsed" aria-expanded="false"> -->
                <a href="#" @click.prevent="toggleMenu('dashboard')">
                  <i class="fas fa-home"></i>
                  <p>User Management</p>
                  <span class="caret" :class="{ rotated: activeMenu === 'dashboard' }"></span>
                </a>
                <!-- <div class="collapse" id="dashboard"> -->
                <div v-show="activeMenu === 'dashboard'">
                  <ul class="nav nav-collapse">

                    <li>
                      <a href="/user-listing">
                        <span class="sub-item">Dashboard</span>
                      </a>
                    </li>


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

              <!-- Daily Sanction Module -->
            <li class="nav-item ">
              <!-- <a data-bs-toggle="collapse" href="#daily-sanction" class="collapsed" aria-expanded="false"> -->
              <a href="#" @click.prevent="toggleMenu('daily-sanction')">
                <i class="fas fa-pen-square"></i>
                <p>Daily Sanction Module</p>
                <span class="caret" :class="{ rotated: activeMenu === 'daily-sanction' }"></span>
              </a>
              <!-- <div class="collapse" id="daily-sanction"> -->
              <div v-show="activeMenu === 'daily-sanction'">
                <ul class="nav nav-collapse">
                  <li>
                    <Link :href="route('daily-sanction')" class="nav-link">
                      <span class="sub-item">Daily Sanction Form</span>
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



              <!-- Budget Allocation Module -->
            <li class="nav-item ">
              <a href="#" @click.prevent="toggleMenu('budget')">
                <i class="fas fa-pen-square"></i>
                <p>Budget Allocation Module</p>
                <span class="caret" :class="{ rotated: activeMenu === 'budget' }"></span>
              </a>
              <div v-show="activeMenu === 'budget'">
                <ul class="nav nav-collapse">
                  <li>
                    <Link :href="route('budget-phase')" class="nav-link">
                      <span class="sub-item">Add details of BE/FE/RE</span>
                    </Link>
                  </li>
                  <li>
                    <Link :href="route('fund-allocation')" class="nav-link">
                      <span class="sub-item">Fund Allocation</span>
                    </Link>
                  </li>
                </ul>
              </div>
            </li>

          
        

            <!-- Budget Head -->
            <li class="nav-item">
              <a href="#" @click.prevent="toggleMenu('base')">
                <i class="fas fa-layer-group"></i>
                <p>Budget Head</p>
                <span class="caret" :class="{ rotated: activeMenu === 'base' }"></span>
              </a>
              <div v-show="activeMenu === 'base'">
                <ul class="nav nav-collapse">
                  <li><a href="/components/avatars"><span class="sub-item">Budget Heads</span></a></li>
                  <li><a href="/components/buttons"><span class="sub-item">State/UTs</span></a></li>
                  <li><a href="/components/buttons"><span class="sub-item">States/UTs-PD/Component</span></a></li>
                </ul>
              </div>
            </li>
              <!-- <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Components</h4>
              </li> -->
              
              <!-- <li class="nav-item">
                <a data-bs-toggle="collapse" href="#forms">
                  <i class="fas fa-pen-square"></i>
                  <p>States Tagging</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="forms">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="forms/forms.html">
                        <span class="sub-item">Link 1</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li> -->
              <!-- <li class="nav-item">
                <a data-bs-toggle="collapse" href="#tables">
                  <i class="fas fa-table"></i>
                  <p>Admin List</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="tables">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="tables/tables.html">
                        <span class="sub-item">Basic Table</span>
                      </a>
                    </li>
                    <li>
                      <a href="tables/datatables.html">
                        <span class="sub-item">Datatables</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li> -->
            </ul>
          </div>
        </div>
      </div>

      <!-- End Sidebar -->
      </div>
    </div>
</template>

<!-- <script setup>
import { Link } from '@inertiajs/vue3'
</script> -->
<script setup>
// import { onMounted } from 'vue'
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
// import Collapse from 'bootstrap/js/dist/collapse'

const activeMenu = ref('') // This will store the ID of the currently open menu

// Toggle menu on click
const toggleMenu = (menuId) => {
  activeMenu.value = activeMenu.value === menuId ? '' : menuId
}
import Collapse from 'bootstrap/js/dist/collapse'

onMounted(() => {
  document.querySelectorAll('.collapse').forEach(el => {
    new Collapse(el, { toggle: false })
  })
})
</script>

