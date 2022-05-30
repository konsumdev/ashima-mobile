import { Component, OnInit } from '@angular/core';
import { ManagerHoursProvider } from 'src/app/services/manager-hours/manager-hours';
import { MenuController } from '@ionic/angular';
import { AppComponent } from 'src/app/app.component';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import * as API_CONFIG from '../../../services/api-config';

@Component({
  selector: 'app-manager-timesheets',
  templateUrl: './manager-timesheets.page.html',
  styleUrls: ['./manager-timesheets.page.scss'],
})
export class ManagerTimesheetsPage implements OnInit {
  contentType: string;
  apiUrl: API_CONFIG.API;
  baseUrl: API_CONFIG.API.BASE_URI;
  allTsheets: any;
  allTotal: any;
  allPage: any;
  fetchingAll: any;
  tsheets: any;
  curTsheets: any;
  fetchingCur: boolean;
  curPage : any;
  curTotal : any;

  constructor(
    private menu: MenuController,
    public hoursProv: ManagerHoursProvider,
    private mainApp : AppComponent,
    public comCtrl: MyCommonServices,
  ) {
    this.tsheets = "today";
    this.curPage = "1";
    this.allPage = "1";

    this.mainApp.setManagerMenu();
    this.mainApp.apiSessionChecker();

    this.apiUrl = API_CONFIG.API.URL;
    this.baseUrl = API_CONFIG.API.BASE_URI;
    this.contentType = API_CONFIG.CONTENT_TYPE;
  }

  allTimesheets() {
    this.hoursProv.getAllTimesheets(this.allPage).then(res=>{
        // console.log(res);
        if (res) {
            let rslt: any = res;
            this.allTsheets = rslt.all_timesheet_res;

            this.allTotal = parseInt(rslt.total, 10);
            this.allPage = parseInt(rslt.page, 10);
        }            
        this.fetchingAll = false;
    }).catch(err=>{
        console.log(err);
        this.fetchingAll = false;
    });
  }

  currentTimesheets() {
    //getCurrentTimesheets
    this.hoursProv.getCurrentTimesheets(this.curPage).then(res=>{
        // console.log(res);
        if (res) {
            let rslt: any = res;
            this.curTsheets = rslt.all_current_timesheet_res;

            this.curTotal = parseInt(rslt.total, 10);
            this.curPage = parseInt(rslt.page, 10);
        }            
        this.fetchingCur = false;
    }).catch(err=>{
        console.log(err);
        this.fetchingCur = false;
    });
  }

  ngOnInit() {
    // this.menu.toggle();
    this.menu.enable(false, 'employeeMenu');
    this.menu.enable(true, 'managerMenu');
    this.currentTimesheets();
    this.allTimesheets();
  }

}
