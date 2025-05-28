<template>
  <div class="sidebar">
    <div class="sidebar-logo">
      <!-- Logo Header -->
      <div class="logo-header">
        <a href="/login" class="logo">
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
    </div>

    <div class="sidebar-wrapper scrollbar scrollbar-inner">
      <div class="sidebar-content">
        <ul class="nav nav-secondary">
          
          <!-- User Management -->
          <li class="nav-item active">
            <a data-bs-toggle="collapse" href="#userManagement" class="collapsed" aria-expanded="false">
              <i class="fas fa-home"></i>
              <p>User Management</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="userManagement">
              <ul class="nav nav-collapse">
                <li>
                  <a href="/dashboard">
                    <span class="sub-item">Dashboard</span>
                  </a>
                </li>
                <li>
                  <a href="/create-user">
                    <span class="sub-item">Create User</span>
                  </a>
                </li>
                <li>
                  <a href="/update-user">
                    <span class="sub-item">Update/Delete User</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>

          <!-- Budget Allocation Module -->
          <li class="nav-item ">
            <a data-bs-toggle="collapse" href="#budget" class="collapsed" aria-expanded="false">
              <i class="fas fa-pen-square"></i>
              <p>Budget Allocation Module</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="budget">
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
            <a data-bs-toggle="collapse" href="#base">
              <i class="fas fa-layer-group"></i>
              <p>Budget Head</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="base">
              <ul class="nav nav-collapse">
                <li><a href="/components/avatars"><span class="sub-item">Budget Heads</span></a></li>
                <li><a href="/components/buttons"><span class="sub-item">State/UTs</span></a></li>
                <li><a href="/components/buttons"><span class="sub-item">States/UTs-PD/Component</span></a></li>
              </ul>
            </div>
          </li>

          <!-- States Tagging -->
          <!-- <li class="nav-item">
            <a data-bs-toggle="collapse" href="#forms">
              <i class="fas fa-pen-square"></i>
              <p>States Tagging</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="forms">
              <ul class="nav nav-collapse">
                <li><a href="/forms/forms"><span class="sub-item">Link 1</span></a></li>
              </ul>
            </div>
          </li>-->

          <!-- Admin List -->
         <!-- <li class="nav-item">
            <a data-bs-toggle="collapse" href="#tables">
              <i class="fas fa-table"></i>
              <p>Admin List</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="tables">
              <ul class="nav nav-collapse">
                <li><a href="/tables/basic"><span class="sub-item">Basic Table</span></a></li>
                <li><a href="/tables/datatables"><span class="sub-item">Datatables</span></a></li>
              </ul>
            </div>
          </li>-->

        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>
