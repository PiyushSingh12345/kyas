<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
        <div class="container">
          <div class="page-inner allinsideform">
            <div class="page-header">
              <h3 class="fw-bold mb-3">Daily Sanction Module</h3>
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
                     <!-- <Link :href="route('daily-sanction')" class="btn btn-primary me-1" style="margin-left:91%">ADD</Link> -->

                  </div>
                  <div class="card-body">
                    
                    <div class="table-responsive mt-1">
                      <table class="table table-bordered table-head-bg-primary">
                        <thead>
                        <tr>
                          <th>Financial year</th>
                          <th>State</th>
                          <th>SLS</th>
                          <th>Full SLS Name</th>
                          <th>PD</th>
                          <th>Date</th>
                          <th>Daily Sanction No</th>
                          <th>Daily Sanction BH wise amount (₹ In Lakhs) - State Total</th>
                          <th>Remarks</th>
                        </tr>
                        </thead>
                        <tbody>
                          <!-- <tr>
                            <td>2024-25</td>
                            <td>UP</td>
                            <td>xyz</td>
                            <td>ty</td>
                            <td>01-04-2024</td>
                            <td>1</td>
                            <td>₹50,000</td>
                            <td>Approved</td>
                          </tr> -->

                          <tr v-for="(item, index) in motherSanctions" :key="item.id">
                            <!-- <td>{{ index + 1 }}</td> -->
                            <td>{{ item.financial_year }}</td>
                            <td>{{ item.state.name }}</td>
                            <td>{{ item.sls_name }}</td>
                            <td>{{ item.full_sls_name || '-' }}</td>
                            <td>{{ item.sls_pd || '-' }}</td>
                            <!-- show date in dd-mm-yyyy format for example 01-04-2024 --> 
                            <td>{{ formatDate(item.ds_date) }} </td>
                            <td>{{ item.ifd_no }}</td>
                            <td>{{ item.state_total_amount || 0 }}</td>
                            <td>{{ item.remark }}</td>

                          </tr>
                        </tbody>
                      </table>

                    </div>

                    <!-- <div class="table-responsive">
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
                            <td>{{ formatDate(item.ds_date) }}</td>
                            <td>{{ item.mother_sanction }}</td>
                            <td>{{ item.ifd_no }}</td>
                            <td>{{ item.sls_name }}</td>

                          </tr>
                        </tbody>

                      </table>
                    </div> -->
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

// Function to format date to dd-mm-yyyy format
const formatDate = (dateString) => {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString; // Return original if invalid date
    
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    return `${day}-${month}-${year}`;
  } catch (error) {
    console.error('Error formatting date:', error);
    return dateString; // Return original if error occurs
  }
}

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


