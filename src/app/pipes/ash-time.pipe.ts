import { Pipe, PipeTransform } from '@angular/core';
import * as moment from 'moment';

@Pipe({
  name: 'ashTime'
})
export class AshTimePipe implements PipeTransform {

  transform(value: string) {
    return moment(value, "HH:mm:ss").format("hh:mm A");
}

}
