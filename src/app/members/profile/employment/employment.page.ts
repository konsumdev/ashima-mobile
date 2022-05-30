import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-employment',
  templateUrl: './employment.page.html',
  styleUrls: ['./employment.page.scss'],
})
export class EmploymentPage implements OnInit {
  pDetails: any;
  empDetails: any;
  empHistory: any;

  constructor(
    private route: ActivatedRoute, 
    private router: Router,
  ) { 
    this.pDetails = 'basicInfo';

    this.route.queryParams.subscribe(params => {
      if (this.router.getCurrentNavigation().extras.state) {
        this.empDetails = this.router.getCurrentNavigation().extras.state.empDetails;
        this.empHistory = this.router.getCurrentNavigation().extras.state.empHstry;
      }
    });
  }

  ngOnInit() {
  }

}
