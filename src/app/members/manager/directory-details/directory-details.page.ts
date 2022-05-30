import { Component, OnInit } from '@angular/core';
import * as API_CONFIG from '../../../services/api-config';
import { ManagerWorkforceProvider } from 'src/app/services/manager-workforce/manager-workforce';
import { MenuController } from '@ionic/angular';
import { AppComponent } from 'src/app/app.component';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-directory-details',
  templateUrl: './directory-details.page.html',
  styleUrls: ['./directory-details.page.scss'],
})
export class DirectoryDetailsPage implements OnInit {
  contentType: string;
  apiUrl: API_CONFIG.API;
  baseUrl: API_CONFIG.API.BASE_URI;
  profImg: any;
  passedDetails: any;
  fetching: any;
  employee: any;
  tsheets: any;
  //router: any;
  weekdays: any;

  constructor(
    private menu: MenuController,
    public workProv: ManagerWorkforceProvider,
    private mainApp : AppComponent,
    public comCtrl: MyCommonServices,
    private route: ActivatedRoute,
    private router: Router,
  ) {
    this.mainApp.setManagerMenu();
    this.mainApp.apiSessionChecker();
    this.profImg = 'assets/imgs/default-profile-pic.png';

    this.apiUrl = API_CONFIG.API.URL;
    this.baseUrl = API_CONFIG.API.BASE_URI;
    this.contentType = API_CONFIG.CONTENT_TYPE;
    this.tsheets = "contact";

    this.weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  }

  
  getEmpDetails() {
    this.route.queryParams.subscribe(params => {
      if (this.router.getCurrentNavigation().extras.state) {
        this.passedDetails = this.router.getCurrentNavigation().extras.state.tsDetails;
      }
    });
        
    //this.passedDetails = this.navParams.get('tsDetails');
    console.log(this.passedDetails);
    if (this.passedDetails) {
        let spnr = this.comCtrl.presentLoadingDefault();

        this.workProv.getDetails(this.passedDetails).then(res=>{
            this.comCtrl.dismissLoading(spnr);
            console.log(res);
            let details: any = res;
            if (details.profile_pic) {
                this.profImg = (details.profile_pic) ? this.apiUrl + "/uploads/companies/" + details.company_id + "/" + details.profile_pic : 'assets/imgs/default-profile-pic.png';
                
            }
            
            this.employee = details;
           // this.fetching = false;
        }).catch(err=>{
            console.log(err);
        });
    }
  }

  isArray(obj : any ) {
    return Array.isArray(obj)
  }

  ngOnInit() {
    // this.menu.toggle();
    this.menu.enable(false, 'employeeMenu');
    this.menu.enable(true, 'managerMenu');
    this.getEmpDetails();
  }
}
