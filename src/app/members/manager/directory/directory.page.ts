import { Component, OnInit } from '@angular/core';
import { MenuController } from '@ionic/angular';
import { AppComponent } from 'src/app/app.component';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import * as API_CONFIG from '../../../services/api-config';
import { ManagerWorkforceProvider } from 'src/app/services/manager-workforce/manager-workforce';
import { Router, NavigationExtras } from '@angular/router';

@Component({
  selector: 'app-directory',
  templateUrl: './directory.page.html',
  styleUrls: ['./directory.page.scss'],
})
export class DirectoryPage implements OnInit {
  contentType: string;
  apiUrl: API_CONFIG.API;
  baseUrl: API_CONFIG.API.BASE_URI;
  
  employees: any;
  availableLetters: any;
  fetching: any;
  content: any;

  constructor(
    private menu: MenuController,
    public workProv: ManagerWorkforceProvider,
    private mainApp : AppComponent,
    public comCtrl: MyCommonServices,
    private router: Router,
  ) {
    this.mainApp.setManagerMenu();
    this.mainApp.apiSessionChecker();
    this.availableLetters = [];

    this.apiUrl = API_CONFIG.API.URL;
    this.baseUrl = API_CONFIG.API.BASE_URI;
    this.contentType = API_CONFIG.CONTENT_TYPE;
  }

  getEmployees() {
    this.workProv.getEmployeeList().then(res=>{
        
      if (res) {
          let rslt: any = res;

          let sortedContacts = rslt.sort();
          
          let currentLetter = false;
          let currentContacts = [];
          this.employees = [];

          sortedContacts.forEach((value, index) => {
            if(value.last_name.charAt(0) != currentLetter){
  
                currentLetter = value.last_name.charAt(0);
  
                let newGroup = {
                    letter: currentLetter,
                    contacts: []
                };
  
                currentContacts = newGroup.contacts;
                this.employees.push(newGroup);
                                
                if (this.availableLetters.indexOf(currentLetter) > -1) {

                } else {
                    this.availableLetters.push(currentLetter);
                }
            }
            currentContacts.push(value);
        });
      }
      this.fetching = false;
    }).catch(err=>{
        console.log(err);
        this.fetching = false;
    });
  }

  /*calculateDimensionsForSidebar() {
      return {
        top: this.content.contentTop + 'px',
        height: (this.content.getContentDimensions().contentHeight - this.content.contentTop) + 'px'
      }
  }*/

  letterChecker(letter: any) {
    if (this.availableLetters.indexOf(letter) > -1) {
        return false;
    }
    return true;
  }

  getDetails(id: any) {
    let navigationExtras: NavigationExtras = {
      state: {
        tsDetails: id
      }
    };

    this.router.navigate(['/members/directory-details'], navigationExtras);
  }

  ngOnInit() {
    // this.menu.toggle();
    this.menu.enable(false, 'employeeMenu');
    this.menu.enable(true, 'managerMenu');
    this.getEmployees();
  }

}
