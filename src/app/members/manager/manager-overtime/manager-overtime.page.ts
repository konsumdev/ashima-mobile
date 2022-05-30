import { Component, OnInit } from '@angular/core';
import * as API_CONFIG from '../../../services/api-config';
import { MenuController } from '@ionic/angular';
import { AppComponent } from 'src/app/app.component';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import { ManagerHoursProvider } from 'src/app/services/manager-hours/manager-hours';
import { formatDate } from '@angular/common';

@Component({
  selector: 'app-manager-overtime',
  templateUrl: './manager-overtime.page.html',
  styleUrls: ['./manager-overtime.page.scss'],
})
export class ManagerOvertimePage implements OnInit {
  contentType: string;
  apiUrl: API_CONFIG.API;
  baseUrl: API_CONFIG.API.BASE_URI;
  overtimes: any;
  page: any;
  total: any;
  fetching: any;
  myDate: any = new Date().toISOString();

  constructor(
    private menu: MenuController,
    public hoursProv: ManagerHoursProvider,
    private mainApp : AppComponent,
    public comCtrl: MyCommonServices,
  ) {
    this.mainApp.setManagerMenu();
    this.mainApp.apiSessionChecker();

    this.apiUrl = API_CONFIG.API.URL;
    this.baseUrl = API_CONFIG.API.BASE_URI;
    this.contentType = API_CONFIG.CONTENT_TYPE;
  }

  getOvertimes() {
    let now = new Date(this.myDate);
    let cd = formatDate(now, 'yyyy-MM-dd', 'en-US');
    
    this.hoursProv.getOvertimeList(this.page+'',cd).then(res=>{
        console.log(res);
        if (res) {
            let rslt: any = res;
            this.overtimes = rslt.all_overtimes_res;
            this.page = parseInt(rslt.page, 10);
            this.total = parseInt(rslt.total, 10);
        }
        this.fetching = false;
    }).catch(err=>{
        console.log(err);
        this.fetching = false;
    });
  }

  prevDate() {
    let now = new Date();
    let cd = formatDate(now, 'yyyy-MM-dd', 'en-US');
    var mmnt = now.setDate(now.getDate() - 1);
    
    this.myDate = new Date(mmnt).toISOString();
    this.overtimes = null;
    this.page = "1";
    this.total = null;
    this.fetching = true;

    this.getOvertimes();
  }

  nextDate() {
    let now = new Date();
    let cd = formatDate(now, 'yyyy-MM-dd', 'en-US');
    var mmnt = now.setDate(now.getDate() + 1);
    var minusday = now.setDate(now.getDate() - 1);
    
    this.myDate = new Date(mmnt).toISOString();
    this.overtimes = null;
    this.page = "1";
    this.total = null;
    this.fetching = true;

    this.getOvertimes();
  }

  onChangeDate() {
    this.page = "1";
    this.overtimes = "";
    this.total = "";
    this.fetching = true;
    this.getOvertimes();
  }

  ngOnInit() {
    // this.menu.toggle();
    this.menu.enable(false, 'employeeMenu');
    this.menu.enable(true, 'managerMenu');
    this.getOvertimes();
  }

}
