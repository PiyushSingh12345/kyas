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
                  <a href="#">States/UTs - PD/Component - SLS IDs Mapping </a>
                </li>
              </ul>
            </div>

            
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <div class="card-title">State - wise list of PD/Component and SLS</div>
                  </div>
                  <div class="card-body">

<div class="table-responsive mt-1">
                      <table class="table table-bordered table-head-bg-primary">
                        
                        <tbody>

                          <tr>
                          
                        
                            <td><label for="html" class="highlight_textbox">State/UT</label></td>
                            <td><label for="html" class="highlight_textbox">PD/Component</label></td>
                            <td><label for="html" class="highlight_textbox">SLS ID</label></td>
                        
                          </tr>

                          <tr>
                          
                          
                            <td><select name="user" id="usertype" class="form-select">
                            <option value="volvo">--- Select ---</option>
                            </select></td>
                            <td><input type="text" class="form-control" placeholder="" ></td>
                            <td><input type="text" class="form-control" placeholder="" ></td>
                        
                          </tr>



                        


                          
                          
                        
                        </tbody>
                      </table>
</div>
<div class="col-md-12 col-lg-12">
                            <button class="btn btn-primary me-1 mb-4">+ Add New</button>
                          </div>
<div class="col-12 d-flex justify-content-center">
                        <button class="btn btn-success me-1">Submit</button>
                        <button class="btn btn-danger me-1">Reset</button>
                      </div>


                    <div class="table-responsive mt-4">
                      <table class="table table-bordered table-head-bg-primary">
                        <thead>
                          <tr> 
                            <th>S. No.</th>
                            <th>State/UT</th>
                            <th>PD/Component</th>
                            
                            <th>SLS ID</th>
                            
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <th scope="row">1</th>
                            <td>---</td>
                            <td>---</td>
                            <td>---</td>
                            
                          </tr>
                          <tr>
                            <th scope="row">2</th>
                            <td>---</td>
                            <td>---</td>
                            <td>---</td>
                            
                          </tr>


                          <tr>
                            <th scope="row">3</th>
                            <td>---</td>
                            <td>---</td>
                            <td>---</td>
                            
                          </tr>

                          
                          <tr>
                            <th scope="row">4</th>
                            <td>---</td>
                            <td>---</td>
                            <td>---</td>
                            
                          </tr>

                            <tr>
                            <th scope="row">5</th>
                            <td>---</td>
                            <td>---</td>
                            <td>---</td>
                            
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

<script>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

export default {
  name: 'StateUTsPD',
  components: {
    Header,
    Sidebar,
    Footer
  }
};
</script>
