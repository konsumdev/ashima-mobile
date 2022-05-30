import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { ProfileProvider } from 'src/app/services/profile/profile';
import * as API_CONFIG from '../../../services/api-config';
import { NavigationExtras, Router } from '@angular/router';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.page.html',
  styleUrls: ['./profile.page.scss'],
})
export class ProfilePage implements OnInit {
  profileDet: any;
  dependents: any;
  empHistory: any;
  compensation: any;
  compHistory: any;
  profImg: string = 'assets/imgs/default-profile-pic.png';
  profName: string;
  profCloudId: string;
  profPosition: string;
  profEmail: string;
  profPriMobile: string;
  apiUrl : any;

  constructor(
    private mainApp : AppComponent,
    private profProv : ProfileProvider,
    private router: Router,
  ) { 
    this.mainApp.apiSessionChecker();
    this.apiUrl = API_CONFIG.API.BASE_URI;
  }

  ngOnInit() {
    this.getEmpProfile();
  }

  getEmpProfile() {
    this.profProv.getEmployeeProfile().then( res => {
        // console.log(res);
        let profRes : any;
        let detProf : any;
        detProf = res;

        if (detProf) {
            profRes = detProf.profile;
            // console.log(profRes);
            this.profileDet = profRes;
            this.profImg = (profRes.profile_image) ? this.apiUrl + "uploads/companies/" + profRes.company_id + "/" + profRes.profile_image : 'assets/imgs/default-profile-pic.png';
            this.profCloudId = profRes.payroll_cloud_id;
            this.profEmail = profRes.email;
            this.profPosition = profRes.position_name;
            this.profPriMobile = profRes.login_mobile_number;
            this.profName = profRes.first_name + " " + profRes.last_name;
            this.dependents = detProf.dependents;
            this.empHistory = detProf.employment_history;
            this.compensation = detProf.compensation;
            this.compHistory = detProf.compensation_history;
        }
    }).catch(error => {            
        console.log(error);
    });
  }
  gotoPersonal() {        
    let navigationExtras: NavigationExtras = {
      state: {
        details: this.profileDet,
        dependents: this.dependents
      }
    };
    this.router.navigate(['/members/personal'], navigationExtras);
  }

  gotoEmployment() {
    let navigationExtras: NavigationExtras = {
      state: {
        empDetails: this.profileDet,
        empHstry : this.empHistory
      }
    };
    this.router.navigate(['/members/employment'], navigationExtras);
  }

  gotoCompensation() {
    let navigationExtras: NavigationExtras = {
      state: {
        compDetails: this.compensation,
        comHistory: this.compHistory
      }
    };
    this.router.navigate(['/members/compensation'], navigationExtras);
  }

}
