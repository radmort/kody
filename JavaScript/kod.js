 
 fetch("transactions.json")
      .then(res => res.json())
      .then(data => {
        const result = transform(data);
        console.log(result);
      })
      
     

    function transform(oldData) {
      const newDataSet = separe(oldData, 'year', 'amount', 'createdAt', 'customerName', 'state');

      const grouped = newDataSet
        .filter(item => item.state === "canceled")
        .reduce((newObj, item) => ({
          ...newObj,
          [item.year]: [...(newObj[item.year] || []), item]
        }), {});

      const groupYear = Object.entries(grouped)
        .sort((a, b) => Number(a[0]) - Number(b[0])) // vzostupne
        .map(([year, items]) => [
          year,
          items
            .sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt)) // vzostupne podľa dátumu
            .map(({ amount, createdAt, customerName }) => ({ amount, createdAt, customerName }))
        ]);

      return Object.fromEntries(groupYear);
    }

    function separe(array, ...keys) {
      return array.map(x =>
        keys.reduce((obj, key) =>
          Object.assign(obj, { [key]: x[key] }),
          {}
        )
      );
    }