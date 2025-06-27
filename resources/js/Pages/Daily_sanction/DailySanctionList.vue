<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
        <div class="container">
          <div class="page-inner allinsideform">
            <div class="page-header">
              <h3 class="fw-bold mb-3">Master Data </h3>
              <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                  <a href="login.html">
                    <i class="icon-home"></i>
                  </a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="#">Daily Sanction List </a>
                </li>
              </ul>

            </div>
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <div class="card-title">Daily Sanction List</div>
                     <Link :href="route('dily-sanction')" class="btn btn-primary me-1" style="margin-left:91%">ADD</Link>

                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-bordered table-head-bg-primary">
                        <thead>
                          <tr>
                            <th>S. No.</th>
                            <th>Financial year</th>
                            <th>State</th>
                            <th>DS Date</th>
                            <th>Mother Sanction</th>
                          
                            <th>IFD No.</th>
                           
                            <th>SLS ID</th>
                            
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, index) in motherSanctions" :key="item.id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ item.financial_year }}</td>
                            <td>{{ item.state.name }}</td>
                            <td>{{ item.ds_date }}</td>
                            <td>{{ item.mother_sanction }}</td>
                            <td>{{ item.ifd_no }}</td>
                            <td>{{ item.sls_name }}</td>

                          </tr>
                        </tbody>

                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <Footer />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'

import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const motherSanctions = ref([])

onMounted(async () => {
  try {
    const res = await fetch('/api/daily-sanctions-list');
    if (res.ok) {
      motherSanctions.value = await res.json();
    } else {
      console.error('Failed to fetch data');
    }
  } catch (error) {
    console.error('Error fetching data:', error);
  }
});
</script>


