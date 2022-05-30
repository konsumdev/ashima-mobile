import { Component, OnInit } from '@angular/core';
import { AllowanceProvider } from 'src/app/services/allowance/allowance';
import { AppComponent } from 'src/app/app.component';

@Component({
  selector: 'app-allowances',
  templateUrl: './allowances.page.html',
  styleUrls: ['./allowances.page.scss'],
})
export class AllowancesPage implements OnInit {

  allowances: any;
  fakeUsers: Array<any>;
  fetchingAll: any;
  

  constructor(
    private allwProv: AllowanceProvider,
    private mainApp : AppComponent,
  ) {
    this.mainApp.apiSessionChecker();
    this.fakeUsers = new Array(10);
    this.fetchingAll = true;
  }

  ngOnInit() {    
    this.getEmployeeAllowance();
  }

  getEmployeeAllowance() {
    this.allwProv.getAllowances().then( res => {
      this.fetchingAll = false;
        let rslt: any = res;

        if (rslt) {
            if (rslt.result) {
                this.allowances = rslt.allowances;
            }
        }
    }).catch( error => {
        this.fetchingAll = false;
        console.log(error);
    });
}

}
